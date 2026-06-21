```mermaid
graph LR
    subgraph Kolom_Entitas [Entitas Eksternal]
        cust[Pelanggan]
        staff[Staff / Kasir]
        adm[Admin]
        arima[Service ARIMA Python]
    end

graph LR
    %% Pengaturan Subgraph untuk Memisahkan Kolom
    subgraph Kolom_Entitas [Entitas Eksternal]
        cust[Pelanggan]
        staff[Staff / Kasir]
        adm[Admin]
        arima[Service ARIMA Python]
    end

    subgraph Kolom_Proses [Proses Utama]
        p1((1.0 Login & Otentikasi))
        p2((2.0 Kelola Data Master))
        p3((3.0 Transaksi Omnichannel))
        p4((4.0 Forecasting & Pelaporan))
    end

    subgraph Kolom_DB [Penyimpanan Data]
        d1[(Tabel Users)]
        d2[(Tabel Products & Categories)]
        d3[(Tabel Orders & Items)]
        d4[(Tabel Prediction Logs)]
    end

    %% Hubungan Aliran Data Kolom 1 ke Kolom 2
    cust -->|Data Login| p1
    p1 -->|Info Login| cust
    staff -->|Data Login| p1
    p1 -->|Info Login| staff
    adm -->|Data Login| p1
    p1 -->|Info Login| adm

    adm -->|Data Master| p2
    p2 -->|Info Kelola| adm

    cust -->|Order Online| p3
    p3 -->|Status & Struk| cust
    staff -->|Transaksi POS| p3
    p3 -->|Struk POS| staff

    adm -->|Parameter ARIMA| p4
    p4 -->|Visualisasi Prediksi| adm
    p4 -->|Payload Historis| arima
    arima -->|Hasil Forecast| p4

    %% Hubungan Aliran Data Kolom 2 ke Kolom 3
    p1 -->|Verifikasi Akun| d1
    d1 -->|Data User Valid| p1
    p2 -->|Update Data Staff| d1
    p2 -->|Manipulasi Katalog| d2
    d2 -->|Data Katalog Berubah| p2

    p3 -->|Potong Stok Produk| d2
    d2 -->|Sisa Stok Real-time| p3
    p3 -->|Simpan Invoice Order| d3
    d3 -->|Rekap Transaksi| p3

    p4 -->|Tarik Tren Penjualan| d3
    d3 -->|Data Deret Waktu| p4
    p4 -->|Log Prediksi Baru| d4
    d4 -->|Ambil Riwayat Prediksi| p4
