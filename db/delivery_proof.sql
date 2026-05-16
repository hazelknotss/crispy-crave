-- Proof of delivery on orders (also auto-added on first use)
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `delivery_proof_file` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `delivery_proof_note` VARCHAR(500) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `delivery_proof_at` DATETIME NULL DEFAULT NULL;
