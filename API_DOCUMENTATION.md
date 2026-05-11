# API Documentation - Wiro Expense Tracker Module

Dokumentasi ini menjelaskan endpoint API yang digunakan untuk sinkronisasi data antara aplikasi Flutter (Android) dan backend Laravel.

## Base URL
`http://localhost/api` (Sesuaikan dengan domain/IP server Anda)

---

## 1. Accounts (Akun/Rekening)

### Get Accounts
Mengambil daftar akun berdasarkan tipe.
- **Endpoint:** `GET /tracker/accounts`
- **Query Params:**
    - `type` (optional): `personal` | `company`
- **Response:** `200 OK`
```json
[
  {
    "id": "uuid-string",
    "name": "BCA Personal",
    "type": "personal",
    "balance": 5000000
  }
]
```

### Store Account
- **Endpoint:** `POST /tracker/accounts`
- **Body:**
```json
{
  "id": "uuid-string (opsional)",
  "name": "Cash",
  "type": "personal",
  "balance": 0
}
```

---

## 2. Categories (Kategori)

### Get Categories
- **Endpoint:** `GET /tracker/categories`
- **Query Params:**
    - `type` (optional): `personal` | `company`
- **Response:** `200 OK`

---

## 3. Expenses (Transaksi/Pengeluaran)

### Get Expenses
- **Endpoint:** `GET /tracker/expenses`
- **Query Params:**
    - `type` (optional): `personal` | `company`
    - `start_date` (optional): `YYYY-MM-DD`
    - `end_date` (optional): `YYYY-MM-DD`
- **Response:** `200 OK` (Paginated)

### Sync Expenses (Bulk Update/Create)
Endpoint utama yang digunakan aplikasi Flutter untuk mengirimkan data offline ke server.
- **Endpoint:** `POST /tracker/expenses/sync`
- **Body:**
```json
{
  "expenses": [
    {
      "id": "uuid-v4-string",
      "account_id": "uuid-account-id",
      "category_id": "uuid-category-id",
      "type": "personal",
      "amount": 50000,
      "expense_date": "2024-05-10 14:00:00",
      "description": "Makan siang"
    }
  ]
}
```
- **Response:** `200 OK` jika berhasil, `500 Error` jika gagal.

### Delete Expense
- **Endpoint:** `DELETE /tracker/expenses/{id}`

---

## Catatan Teknis
1. **UUID:** Seluruh ID wajib menggunakan format UUID v4.
2. **Sync Status:** Di sisi aplikasi Flutter, setelah mendapatkan response `200 OK` dari endpoint `/sync`, status lokal harus diubah menjadi `synced`.
3. **Authentication:** (Opsional) Gunakan Bearer Token jika API sudah diproteksi oleh Laravel Sanctum/Passport.
