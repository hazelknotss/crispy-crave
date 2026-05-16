-- Customer cancellation fields (auto-added on first use)
ALTER TABLE `orders`
  ADD COLUMN `cancel_reason` VARCHAR(500) NULL DEFAULT NULL,
  ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL;
