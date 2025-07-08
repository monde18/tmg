<?php
// receipt_form.php

// Collect previously submitted values so the form “sticks” on postback
$values = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $_POST;
}
function val($key, $i = null) {
    global $values;
    if ($i !== null) {
        return isset($values[$key][$i]) ? htmlspecialchars($values[$key][$i]) : '';
    }
    return isset($values[$key]) ? htmlspecialchars($values[$key]) : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Official Receipt Entry</title>
  <style>
    /* Set the physical page size when printing */
    @page {
      size: 10cm 20cm;
      margin: 0;
    }
    @media print {
      html, body {
        width: 10cm;
        height: 20cm;
        margin: 0;
        padding: 0;
      }
      .receipt {
        width: 10cm;
        height: 20cm;
        margin: 0;
        box-shadow: none;
        border: 1px solid #000;
      }
    }

    /* On-screen and default styling */
    body {
      background: #f0f0f0;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      padding: 20px 0;
    }
    .receipt {
      width: 10cm;
      height: 20cm;
      background: #fff;
      border: 1px solid #000;
      box-shadow: 0 0 5px rgba(0,0,0,0.2);
      box-sizing: border-box;
      padding: 10px;
      display: flex;
      flex-direction: column;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .header img {
      height: 1.8cm;
    }
    .header .center {
      text-align: center;
      flex: 1;
      line-height: 1.1;
    }
    .header .center h1 {
      margin: 0;
      font-size: 1.2cm;
    }
    .header .center p {
      margin: 2px 0;
      font-size: 0.4cm;
      font-weight: bold;
    }
    .section {
      margin-top: 0.2cm;
    }
    .two-col {
      display: flex;
      justify-content: space-between;
    }
    .two-col .col {
      width: 48%;
      box-sizing: border-box;
    }
    .box {
      border: 1px solid #000;
      padding: 4px;
      box-sizing: border-box;
    }
    .full-row label {
      display: block;
      font-weight: bold;
      font-size: 0.35cm;
      margin-bottom: 2px;
    }
    .full-row input[type="text"],
    .full-row input[type="date"] {
      width: 100%;
      box-sizing: border-box;
      padding: 4px;
      font-size: 0.35cm;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0.2cm;
      font-size: 0.35cm;
    }
    table, th, td {
      border: 1px solid #000;
    }
    th, td {
      padding: 4px;
    }
    th {
      background: #e0e0e0;
    }
    td input {
      width: 100%;
      box-sizing: border-box;
      padding: 2px;
      border: none;
      font-size: 0.35cm;
    }
    .checkboxes {
      display: flex;
      gap: 0.5cm;
      margin-top: 0.2cm;
      font-size: 0.35cm;
    }
    .checkboxes label {
      display: flex;
      align-items: center;
    }
    .checkboxes input {
      margin-right: 4px;
    }
    .bottom {
      display: flex;
      justify-content: space-between;
      margin-top: 0.5cm;
    }
    .bottom .left, .bottom .right {
      width: 48%;
      box-sizing: border-box;
    }
    .bottom label {
      display: block;
      font-weight: bold;
      margin-bottom: 2px;
      font-size: 0.35cm;
    }
    .bottom input {
      width: 100%;
      box-sizing: border-box;
      padding: 4px;
      font-size: 0.35cm;
      border: 1px solid #000;
    }
    .submit {
      text-align: center;
      margin-top: auto;
    }
    .submit button {
      padding: 6px 12px;
      font-size: 0.4cm;
    }
  </style>
</head>
<body>
  <form method="post" action="">
    <div class="receipt">
      <!-- Header with logos and title -->
      <div class="header">
        <img src="logo1.png" alt="Republic Seal">
        <div class="center">
          <h1>OFFICIAL RECEIPT</h1>
          <p>Republic of the Philippines<br>
             Province of Cagayan<br>
             OFFICE OF THE TREASURER</p>
        </div>
        <img src="logo2.png" alt="Province Seal">
      </div>

      <!-- Municipality -->
      <div class="section full-row box">
        <label for="municipality">Municipality</label>
        <input type="text" id="municipality" name="municipality" value="<?= val('municipality') ?>">
      </div>

      <!-- Date and Receipt No. -->
      <div class="section two-col">
        <div class="col box">
          <small>Accountable Form No. 51 | Revised January, 1992</small>
          <label for="date"><strong>DATE</strong></label>
          <input type="date" id="date" name="date" value="<?= val('date') ?>">
        </div>
        <div class="col box" style="text-align:right;">
          <p><strong>ORIGINAL</strong></p>
          <label for="receipt_no"><strong>No.</strong></label>
          <input type="text" id="receipt_no" name="receipt_no" value="<?= val('receipt_no') ?>" style="text-align:right;">
        </div>
      </div>

      <!-- Payor -->
      <div class="section full-row box">
        <label for="payor">PAYOR</label>
        <input type="text" id="payor" name="payor" value="<?= val('payor') ?>">
      </div>

      <!-- Collection Table -->
      <table>
        <thead>
          <tr>
            <th>NATURE OF COLLECTION</th>
            <th>FUND AND ACCOUNT CODE</th>
            <th>AMOUNT (₱)</th>
          </tr>
        </thead>
        <tbody>
        <?php for($i=0; $i<10; $i++): ?>
          <tr>
            <td><input type="text" name="nature_of_collection[]" value="<?= val('nature_of_collection',$i) ?>"></td>
            <td><input type="text" name="fund_code[]" value="<?= val('fund_code',$i) ?>"></td>
            <td><input type="text" name="amount[]" value="<?= val('amount',$i) ?>"></td>
          </tr>
        <?php endfor; ?>
          <tr>
            <td colspan="2" style="text-align:right;"><strong>Total</strong></td>
            <td><input type="text" name="total" value="<?= val('total') ?>"></td>
          </tr>
        </tbody>
      </table>

      <!-- Amount in Words -->
      <div class="section full-row box">
        <label for="amount_in_words">AMOUNT IN WORDS</label>
        <input type="text" id="amount_in_words" name="amount_in_words" value="<?= val('amount_in_words') ?>">
      </div>

      <!-- Received Method -->
      <div class="section box">
        <label><strong>Received</strong></label>
        <div class="checkboxes">
          <?php 
            $methods = ['Cash','Treasury Warrant','Check','Money Order'];
            foreach($methods as $m): 
          ?>
            <label>
              <input type="checkbox" name="received[]" value="<?= $m ?>"
                <?= in_array($m, $values['received'] ?? []) ? 'checked' : '' ?>>
              <?= $m ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Warrant/Check/MO Number & Date -->
      <div class="section two-col">
        <div class="col box">
          <label for="warrant_number">Treasury Warrant, Check, Money Order Number</label>
          <input type="text" id="warrant_number" name="warrant_number" value="<?= val('warrant_number') ?>">
        </div>
        <div class="col box">
          <label for="warrant_date">Date of Treasury Warrant, Check, Money Order</label>
          <input type="date" id="warrant_date" name="warrant_date" value="<?= val('warrant_date') ?>">
        </div>
      </div>

      <!-- Received Statement & Collecting Officer -->
      <div class="section bottom">
        <div class="left">
          <div class="box" style="height:2.5cm;">
            <p><strong>Received the Amount Stated Above.</strong></p>
          </div>
        </div>
        <div class="right">
          <div class="box" style="height:2.5cm;">
            <label for="collecting_officer">Collecting Officer</label>
            <input type="text" id="collecting_officer" name="collecting_officer" value="<?= val('collecting_officer') ?>">
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="submit">
        <button type="submit">Save Receipt</button>
      </div>
    </div>
  </form>
</body>
</html>
