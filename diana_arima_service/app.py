import os
import time
import numpy as np
import pandas as pd
from flask import Flask, request, jsonify
from flask_cors import CORS
from pmdarima.arima import auto_arima

app = Flask(__name__)
CORS(app)

@app.route('/api/v1/health', methods=['GET'])
def health_check():
    return jsonify({
        "status": "ok",
        "service": "diana-arima-service",
        "version": "1.0.0"
    }), 200

@app.route('/api/v1/predict', methods=['POST'])
def predict():
    start_time = time.time()
    payload = request.get_json()

    if not payload:
        return jsonify({"message": "Payload JSON kosong atau tidak valid."}), 400

    product_name = payload.get('product_name', 'Produk')
    periods = int(payload.get('forecast_periods', 7))
    tuning_params = payload.get('tuning_parameters', {})
    historical_data = payload.get('historical_data', [])

    # 1. Validasi: Minimal 7 record data historis
    if len(historical_data) < 7:
        return jsonify({
            "message": f"Data historis tidak mencukupi. Dibutuhkan minimal 7 data harian. Data saat ini: {len(historical_data)}."
        }), 422

    try:
        # Convert ke DataFrame Pandas
        df = pd.DataFrame(historical_data)
        df['date'] = pd.to_datetime(df['date'])
        df.set_index('date', inplace=True)
        df.sort_index(inplace=True)

        # 2. Preprocessing: Zero-Fill Missing Dates
        if tuning_params.get('fill_missing_dates', True):
            # Reindex harian lengkap dari tanggal min ke max
            full_range = pd.date_range(start=df.index.min(), end=df.index.max(), freq='D')
            df = df.reindex(full_range, fill_value=0)
            
        # Mengubah index name kembali untuk mempermudah operasional
        df.index.name = 'date'

        # 3. Preprocessing: Outliers Smoothing (IQR Method)
        if tuning_params.get('smooth_outliers', True) and len(df) >= 4:
            q1 = df['total_sales'].quantile(0.25)
            q3 = df['total_sales'].quantile(0.75)
            iqr = q3 - q1
            upper_limit = q3 + 1.5 * iqr
            
            # Capping outliers
            df['total_sales'] = np.clip(df['total_sales'], 0, upper_limit)

        ts_series = df['total_sales'].astype(float)

        # Extract dynamic ARIMA search hyperparameters with sensible defaults
        start_p = int(tuning_params.get('start_p', 0))
        start_q = int(tuning_params.get('start_q', 0))
        max_p = int(tuning_params.get('max_p', 5))
        max_q = int(tuning_params.get('max_q', 5))
        seasonal = bool(tuning_params.get('seasonal', False))
        stepwise = bool(tuning_params.get('stepwise', True))

        # 4. Perhitungan MAPE Adaptif (Resolusi #8)
        if len(ts_series) >= 15:
            # === DATA CUKUP: Train-Test Split 80/20 ===
            split_idx = int(len(ts_series) * 0.8)
            train = ts_series.iloc[:split_idx]
            test = ts_series.iloc[split_idx:]

            # Latih model ARIMA di data Train
            model = auto_arima(
                train, start_p=start_p, start_q=start_q, max_p=max_p, max_q=max_q,
                d=None, seasonal=seasonal, stepwise=stepwise,
                error_action='ignore', suppress_warnings=True
            )

            # Evaluasi out-of-sample (MAPE jujur)
            test_preds = model.predict(n_periods=len(test))
            
            # Rumusan MAPE aman (mencegah pembagian dengan nol)
            mape = np.mean(np.abs((test.values - test_preds.values) / np.clip(test.values, 1, None))) * 100
            mape_method = "out_of_sample"

            # Refit/Update model menggunakan sisa data test untuk forecast final
            model.update(test)
        else:
            # === DATA MINIM: In-sample MAPE + Warning Flag ===
            model = auto_arima(
                ts_series, start_p=start_p, start_q=start_q, max_p=max_p, max_q=max_q,
                d=None, seasonal=seasonal, stepwise=stepwise,
                error_action='ignore', suppress_warnings=True
            )

            # Evaluasi in-sample
            in_sample_preds = model.predict_in_sample()
            
            # Rumusan MAPE aman
            mape = np.mean(np.abs((ts_series.values - in_sample_preds.values) / np.clip(ts_series.values, 1, None))) * 100
            mape_method = "in_sample_warning"

        # 5. Generate Forecast (Ramalkan ke depan)
        forecast_values = model.predict(n_periods=periods)
        
        # Clamp hasil peramalan minimal 0 agar tidak ada proyeksi penjualan negatif
        forecast_values = np.clip(forecast_values, 0, None)

        # 6. Format Proyeksi Hasil Akhir untuk Grafik Visualisasi
        # Kumpulkan data historis untuk grafik
        forecast_result = []
        for dt, val in ts_series.items():
            forecast_result.append({
                "date": dt.strftime('%Y-%m-%d'),
                "sales": float(val),
                "is_forecast": False
            })

        # Kumpulkan data prediksi untuk grafik
        last_date = ts_series.index.max()
        for idx, val in enumerate(forecast_values):
            pred_date = last_date + pd.Timedelta(days=idx + 1)
            forecast_result.append({
                "date": pred_date.strftime('%Y-%m-%d'),
                "sales": float(val),
                "is_forecast": True
            })

        # Ambil representasi String ARIMA Order
        order = model.order
        arima_order_str = f"ARIMA({order[0]},{order[1]},{order[2]})"

        exec_time = int((time.time() - start_time) * 1000)

        return jsonify({
            "status": "success",
            "arima_order": arima_order_str,
            "evaluation": {
                "mape_score": round(float(mape), 2),
                "mape_method": mape_method
            },
            "execution_time_ms": exec_time,
            "forecast_result": forecast_result
        })

    except Exception as e:
        return jsonify({
            "message": "Gagal menjalankan kalkulasi algoritma ARIMA.",
            "error": str(e)
        }), 500

if __name__ == '__main__':
    host = os.getenv('FLASK_HOST', '0.0.0.0')
    port = int(os.getenv('FLASK_PORT', 5000))
    app.run(host=host, port=port, debug=False)
