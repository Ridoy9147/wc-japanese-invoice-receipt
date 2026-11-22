# WooCommerce Japanese Invoice & Receipt (納品書・領収書印刷)

[![WordPress Tested](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![WooCommerce Tested](https://img.shields.io/badge/WooCommerce-6.0%2B%20|%20HPOS%20Ready-purple.svg)](https://woocommerce.com)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Author: Developer Hridoy](https://img.shields.io/badge/Author-Developer%20Hridoy-red.svg)](https://github.com/Ridoy9147)

**WooCommerce Japanese Invoice & Receipt** is a lightweight, zero-dependency WordPress/WooCommerce plugin that generates beautiful, publication-grade Japanese A4 printable and PDF Invoices & Receipts (納品書 兼 領収書).

It is fully compliant with Japan's **Qualified Invoice System (インボイス制度 - 適格請求書発行事業者登録番号)** and includes dual-copy layout (`店舗控え` Shop Copy + `お客様控え` Customer Copy with `— キリトリ線 —` cut line), dynamic logo watermarking, sticky browser print toolbars, bulk order printing, and High-Performance Order Storage (HPOS) support.

---

## ✨ Features

- **🗾 Japanese Business & Legal Compliance:**
  - Designed specifically for the Japanese ecommerce market.
  - Supports the Qualified Invoice System (適格請求書発行事業者登録番号 `T+13桁`).
  - Breakdown of consumption tax (`内消費税等 10%`).
  - Formatted Japanese customer name addresses (`様`), dates (`YYYY年MM月DD日`), and currency (`¥`).

- **📄 Elegant Dual-Copy A4 Print Layout:**
  - Prints two identical copies on a single A4 sheet:
    - **店舗控え (Shop Copy)** with blue badge.
    - **お客様控え (Customer Copy)** with green badge.
    - Separated by an elegant cut-line (`— キリトリ線 (cut here) —`).
  - Automatic fallback or selectable full-page single-copy mode for orders with many items.

- **🪶 Zero External PDF Bloat:**
  - **NO** heavy server-side PDF libraries (no DomPDF, no mPDF, no TCPDF).
  - Uses native HTML5 + CSS3 `@media print` rules.
  - Lightning-fast loading and 1-click "Save as PDF" through standard browser print dialogs.

- **🖼️ Dynamic Logo & Watermark:**
  - Automatically fetches the site's active WordPress custom logo.
  - Displays the crisp brand logo in the invoice header band.
  - Renders a subtle, watermark logo centered in the background (`opacity: 0.04`).

- **⚡ WooCommerce Integration & HPOS Ready:**
  - **Orders List Action:** Quick-print icon button (`🖨️ 納品書`) in the orders table.
  - **Order Edit Screen:** Dedicated Meta Box with "View & Print" and "Direct PDF Print" buttons.
  - **Bulk Printing:** Print multiple orders in a single printable PDF stream with clean page breaks.
  - **Customer Self-Service:** Optional button in `My Account > Orders > View Order` allowing customers to download/print their own receipt.
  - Full compatibility with **High-Performance Order Storage (HPOS)**.

---

## 🚀 Installation

1. Download the `wc-japanese-invoice-receipt.zip` file.
2. Go to your WordPress Admin dashboard: **Plugins > Add New > Upload Plugin**.
3. Choose `wc-japanese-invoice-receipt.zip` and click **Install Now**.
4. Click **Activate Plugin**.
5. Configure your store details under **WooCommerce > Settings > 納品書・領収書 (Japanese Invoice)**.

---

## ⚙️ Configuration (WooCommerce > Settings > 納品書・領収書)

| Setting | Description |
|---|---|
| **屋号・会社名 (Store Name)** | Your business/store name printed on the invoice header. |
| **所在地・住所 (Address)** | Store postal code, address, and contact info. |
| **電話番号 (TEL)** | Store phone number. |
| **適格請求書登録番号 (Invoice Reg. No.)** | Japan Qualified Invoice number (e.g. `T1234567890123`). |
| **印刷レイアウト (Print Layout)** | Dual-copy (`店舗控え + お客様控え`), Single full-page, or Auto. |
| **ロゴ透かし背景 (Logo Watermark)** | Enable/disable faint center logo watermark. |
| **押印・署名欄 (Stamp Boxes)** | Enable/disable official seal & signature boxes. |
| **マイアカウント顧客印刷 (Customer Print)** | Allow customers to download receipts from their My Account. |

---

## 🧑‍💻 Author

- **Developer Hridoy**
- GitHub: [@Ridoy9147](https://github.com/Ridoy9147)

---

## 📜 License

This plugin is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
