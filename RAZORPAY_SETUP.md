# Quick Setup Instructions for Razorpay Integration

## Step 1: Create Database Table

Open phpMyAdmin and run this SQL query:

```sql
CREATE TABLE IF NOT EXISTS `material_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `items` text NOT NULL COMMENT 'JSON encoded cart items',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_id` varchar(255) NOT NULL COMMENT 'Razorpay payment ID',
  `razorpay_order_id` varchar(255) NOT NULL COMMENT 'Razorpay order ID',
  `status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_id` (`payment_id`),
  KEY `razorpay_order_id` (`razorpay_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Step 2: Test the Integration

1. Open `http://localhost/Constructa/material_market.php`
2. Add some materials to cart
3. Click the cart icon (floating button)
4. Click "Proceed to Checkout"
5. Razorpay payment modal should open

## Test Payment Details

### Test Cards
- **Card Number**: 4111 1111 1111 1111
- **CVV**: Any 3 digits (e.g., 123)
- **Expiry**: Any future date (e.g., 12/25)
- **Name**: Any name

### Test UPI
- **UPI ID**: success@razorpay (for successful payment)
- **UPI ID**: failure@razorpay (for failed payment)

## What Happens After Payment?

1. ✅ Payment is verified on backend
2. ✅ Order is saved to `material_orders` table
3. ✅ Success message is shown
4. ✅ Cart is cleared
5. ✅ Order ID is displayed

## Files Created/Modified

✅ `js/shopping_cart.js` - Updated checkout function
✅ `material_market.php` - Added Razorpay SDK
✅ `backend/create_razorpay_order.php` - Creates Razorpay orders
✅ `backend/verify_razorpay_payment.php` - Verifies payments
✅ `backend/migrations/create_material_orders_table.sql` - Database schema

## Troubleshooting

**Issue**: "Server connection error" when clicking checkout
**Solution**: Make sure you've created the `material_orders` table in your database

**Issue**: Payment modal doesn't open
**Solution**: Check browser console for errors. Ensure Razorpay SDK is loaded.

**Issue**: Payment succeeds but order not saved
**Solution**: Check that database table exists and backend files have correct permissions

---

**Ready to test!** 🚀
