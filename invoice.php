<?php
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Initialize DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$dompdf = new Dompdf($options);

// Fetch sale data from the database
require_once 'db.php';
$sale_id = $_GET['sale_id'];
$sql = "SELECT * FROM sales WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();
$sale_data = $sale_result->fetch_assoc();

// Fetch items associated with the sale
$item_sql = "SELECT * FROM sales_items WHERE sale_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $sale_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();

// Check if items exist
if ($item_result->num_rows == 0) {
    echo "No items found for this sale.";
    exit;
}

// Calculate total if not already done
$total = $sale_data['sale_total_amount'];
$discount = $sale_data['total_discount'];
$final_total = $sale_data['final_total'];

$html = '
<html>
<head>
  <style>
    body {
      font-family: "DejaVu Sans", sans-serif;
      margin: 40px;
      font-size: 14px;
      color: #2c3e50;
    }

    .invoice-box {
      max-width: 800px;
      margin: auto;
      padding: 30px;
      border: 1px solid #eee;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,.15);
    }

    .invoice-header {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 40px;
    }

    .header-right {
      text-align: right;
    }

    .header-right h1 {
      font-size: 32px;
      margin: 0;
      color: #34495e;
    }

    .header-right p {
      margin: 4px 0;
      color: #7f8c8d;
    }

    .section-title {
      font-weight: bold;
      margin-top: 20px;
      margin-bottom: 10px;
      color: #2980b9;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #2980b9;
      color: white;
    }

    .total {
      margin-top: 20px;
      text-align: right;
    }

    .total p {
      margin: 4px 0;
      font-weight: bold;
    }

    .terms {
      margin-top: 40px;
      font-size: 12px;
      color: #555;
    }

    .footer {
      border-top: 1px solid #ccc;
      margin-top: 30px;
      padding-top: 20px;
      font-size: 12px;
      display: flex;
      justify-content: space-between;
    }
  </style>
</head>
<body>
  <div class="">
    <div class="invoice-header">
      <div class="header-right">
        <h1>INVOICE</h1>
        <p>Invoice #: ' . $sale_data['sale_id'] . '</p>
        <p>Date: ' . $sale_data['sale_date'] . '</p>
      </div>
    </div>

    <div class="details">
      <div class="section-title">Billed To:</div>
      <p><strong>' . $sale_data['sale_name'] . '</strong><br>
      ' . $sale_data['sale_address'] . '<br>
      Phone: ' . $sale_data['sale_phone_no'] . '<br>
      Other Info: ' . $sale_data['sale_other_info'] . '</p>
      <p><strong>Status:</strong> ' . ($sale_data['status'] == 1 ? 'Paid' : 'Not Paid') . '</p>
      <p><strong>Note:</strong> ' . $sale_data['note'] . '</p>
    </div>

    <table>
      <thead>
        <tr>
          <th>Item Name</th>
          <th>Price (₹)</th>
        </tr>
      </thead>
      <tbody>';
      
while ($item = $item_result->fetch_assoc()) {
    $html .= '
        <tr>
          <td>' . $item['item_name'] . '</td>
          <td>&#8377;' . number_format($item['item_price'], 2) . '</td>
        </tr>';
}

$html .= '
      </tbody>
    </table>

    <div class="total">
      <p>Subtotal: &#8377;' . number_format($total, 2) . '</p>
      <p>Discount: &#8377;' . number_format($discount, 2) . '</p>
      <p><strong>Total Due: &#8377;' . number_format($final_total, 2) . '</strong></p>
    </div>

    <div class="terms">
      <div class="section-title">Terms & Conditions</div>
      <p>1. Payment due within 15 days from the invoice date.</p>
      <p>2. Late payments may incur a 5% fee.</p>
      <p>3. No refunds once design/development work is delivered.</p>
      <p>4. Domain and hosting are billed annually unless otherwise agreed.</p>
      <p>5. Please contact for any billing issues or queries.</p>
    </div>

    <div class="footer">
      <div class="footer-details">
        <p><strong>Rajendrasinh Pahiyar</strong></p>
        <p>Freelance Web Designer & Developer</p>
        <p>Email: rajendra@example.com</p>
        <p>Phone: +91-9876543210</p>
        <p>UPI ID: rajendra@upi</p>
      </div>
      <div>
        <p><strong>Thank you for your business!</strong></p>
        <p>This is a computer-generated invoice.</p>
      </div>
    </div>
  </div>
</body>
</html>';

// Render PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("invoice_" . $sale_data['sale_id'] . ".pdf", array("Attachment" => 1));
?>
