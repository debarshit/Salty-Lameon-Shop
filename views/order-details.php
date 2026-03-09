 <!--=============== BREADCRUMB ===============-->
<section class="breadcrumb">
  <ul class="breadcrumb__list flex container">
    <li><a href="/" class="breadcrumb__link">Home</a></li>
    <li><span class="breadcrumb__link">></span></li>
    <li><span class="breadcrumb__link">Account</span></li>
    <li><span class="breadcrumb__link">></span></li>
    <li><span class="breadcrumb__link">Orders</span></li>
    <li><span class="breadcrumb__link">></span></li>
    <li><span class="breadcrumb__link">Order details</span></li>
  </ul>
</section>
<?php
  $orderId = $_GET['orderId'];
  fetchOrderDetails($orderId);

?>

<link rel="stylesheet" href="assets/css/order-details.css" />