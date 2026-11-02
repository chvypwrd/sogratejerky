<style>
    @page { size: letter; margin: 0.45in; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #111; background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.4; }
    h1, h2, h3, p { margin-top: 0; }
    h1 { font-size: 24px; margin-bottom: 4px; }
    h2 { font-size: 14px; margin: 18px 0 8px; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #111; padding-bottom: 4px; }
    .print-page { max-width: 7.6in; margin: 0 auto; }
    .print-actions { margin: 12px 0 18px; display: flex; gap: 8px; }
    .print-actions a, .print-actions button { padding: 7px 12px; border: 1px solid #111; background: #fff; color: #111; font: inherit; text-decoration: none; cursor: pointer; }
    .print-header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111; padding-bottom: 12px; }
    .print-header img { max-height: 54px; max-width: 220px; object-fit: contain; }
    .print-meta { text-align: right; }
    .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px; margin-top: 14px; }
    .print-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #444; margin-bottom: 2px; }
    .print-value { font-weight: 600; }
    address { font-style: normal; margin: 4px 0 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    th, td { border-bottom: 1px solid #bbb; padding: 6px 5px; vertical-align: top; text-align: left; }
    th { font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
    .num { text-align: right; white-space: nowrap; }
    .center { text-align: center; }
    .check-box { display: inline-block; width: 13px; height: 13px; border: 1px solid #111; vertical-align: middle; }
    .totals { width: 48%; margin-left: auto; margin-top: 10px; }
    .totals td { border-bottom: 1px solid #ddd; }
    .totals tr:last-child td { border-top: 2px solid #111; border-bottom: 0; font-weight: 700; }
    .muted { color: #555; }
    .status { display: inline-block; padding: 2px 6px; border: 1px solid #111; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
    .note-box { border: 1px solid #111; padding: 8px; min-height: 42px; }
    .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 36px; margin-top: 28px; }
    .signature-line { border-top: 1px solid #111; padding-top: 5px; }
    .print-footer { margin-top: 22px; padding-top: 8px; border-top: 1px solid #bbb; font-size: 10px; color: #555; }
    @media print {
        .no-print { display: none !important; }
        body { font-size: 11px; }
        a { color: #111; text-decoration: none; }
        .print-page { max-width: none; }
    }
</style>
