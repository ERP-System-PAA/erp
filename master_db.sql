-- =========================================
-- PAA ERP — INTEGRATION MIGRATION
-- Extends existing erp_db (keeps `users`)
-- =========================================

USE erp_db;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =========================================
-- 1. HR — EMPLOYEES (links to users)
-- =========================================
CREATE TABLE IF NOT EXISTS `employees` (
  `employee_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,              -- ★ links to users.id
  `employee_code` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `position` VARCHAR(50) NOT NULL,
  `department` ENUM('Procurement','Inventory','Production','Sales','Finance','HR','Admin','Delivery') NOT NULL,
  `hire_date` DATE NOT NULL,
  `birthdate` DATE DEFAULT NULL,
  `sss_no` VARCHAR(20) DEFAULT NULL,
  `philhealth_no` VARCHAR(20) DEFAULT NULL,
  `pagibig_no` VARCHAR(20) DEFAULT NULL,
  `basic_salary` DECIMAL(12,2) DEFAULT 0.00,
  `status` ENUM('Active','Inactive','Resigned') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  KEY `fk_emp_user` (`user_id`),
  CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 2. SALES — CUSTOMERS
-- =========================================
CREATE TABLE IF NOT EXISTS `customers` (
  `customer_id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_code` VARCHAR(20) NOT NULL,
  `customer_type` ENUM('B2C','B2B') NOT NULL DEFAULT 'B2C',
  `customer_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `birthdate` DATE DEFAULT NULL,
  `id_verified` TINYINT(1) DEFAULT 0,
  `business_name` VARCHAR(100) DEFAULT NULL,
  `business_permit_no` VARCHAR(50) DEFAULT NULL,
  `credit_limit` DECIMAL(12,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 3. PROCUREMENT — SUPPLIERS
-- =========================================
CREATE TABLE IF NOT EXISTS `suppliers` (
  `supplier_id` INT(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(20) NOT NULL,
  `supplier_name` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `license_no` VARCHAR(50) DEFAULT NULL,
  `is_imported` TINYINT(1) DEFAULT 0,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 4. INVENTORY — WAREHOUSES
-- =========================================
CREATE TABLE IF NOT EXISTS `warehouses` (
  `warehouse_id` INT(11) NOT NULL AUTO_INCREMENT,
  `warehouse_code` VARCHAR(20) NOT NULL,
  `warehouse_name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(150) DEFAULT NULL,
  `warehouse_type` ENUM('Main','Bonded','Bottling','Delivery') NOT NULL DEFAULT 'Main',
  PRIMARY KEY (`warehouse_id`),
  UNIQUE KEY `warehouse_code` (`warehouse_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 5. SHARED MASTER — ITEMS
-- =========================================
CREATE TABLE IF NOT EXISTS `items` (
  `item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_code` VARCHAR(20) NOT NULL,
  `item_name` VARCHAR(100) NOT NULL,
  `item_type` ENUM('Raw Material','Finished Good') NOT NULL,
  `category` ENUM('Whiskey','Vodka','Gin','Rum','Tequila','Brandy','Lambanog','Packaging','Other') NOT NULL,
  `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
  `reorder_level` INT(11) DEFAULT 0,
  `excise_tax_rate` DECIMAL(5,2) DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) DEFAULT 0.00,
  `is_sellable` TINYINT(1) DEFAULT 0,
  `is_purchasable` TINYINT(1) DEFAULT 0,
  `is_producible` TINYINT(1) DEFAULT 0,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `item_code` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;






Production Tables


-- =========================================
-- 6. BOM HEADER
-- =========================================
CREATE TABLE IF NOT EXISTS `bom_header` (
  `bom_id` INT(11) NOT NULL AUTO_INCREMENT,
  `bom_code` VARCHAR(20) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `version` VARCHAR(10) NOT NULL DEFAULT 'v1',
  `yield_qty` DECIMAL(12,2) NOT NULL,
  `yield_uom` VARCHAR(20) DEFAULT 'bottles',
  `effective_date` DATE DEFAULT NULL,
  `status` ENUM('Draft','Approved','Obsolete') DEFAULT 'Draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bom_id`),
  UNIQUE KEY `bom_code` (`bom_code`),
  UNIQUE KEY `uq_bom` (`product_id`,`version`),
  KEY `fk_bomh_product` (`product_id`),
  CONSTRAINT `fk_bomh_product` FOREIGN KEY (`product_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 7. BOM LINES
-- =========================================
CREATE TABLE IF NOT EXISTS `bom_line` (
  `bom_line_id` INT(11) NOT NULL AUTO_INCREMENT,
  `bom_id` INT(11) NOT NULL,
  `material_id` INT(11) NOT NULL,
  `quantity_required` DECIMAL(12,4) NOT NULL,
  `uom` VARCHAR(20) NOT NULL,
  `waste_pct` DECIMAL(5,2) DEFAULT 0.00,
  PRIMARY KEY (`bom_line_id`),
  KEY `fk_boml_bom` (`bom_id`),
  KEY `fk_boml_mat` (`material_id`),
  CONSTRAINT `fk_boml_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom_header` (`bom_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_boml_mat` FOREIGN KEY (`material_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 8. PRODUCTION ORDERS
-- =========================================
CREATE TABLE IF NOT EXISTS `production_orders` (
  `po_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_code` VARCHAR(20) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `bom_id` INT(11) NOT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `planned_qty` DECIMAL(12,2) NOT NULL,
  `actual_qty` DECIMAL(12,2) DEFAULT 0,
  `order_date` DATE NOT NULL,
  `status` ENUM('Planned','Blending','Bottling','Labeling','Completed','Cancelled') DEFAULT 'Planned',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_code` (`po_code`),
  KEY `fk_po_prod` (`product_id`),
  KEY `fk_po_bom` (`bom_id`),
  KEY `fk_po_wh` (`warehouse_id`),
  KEY `fk_po_emp` (`created_by`),
  CONSTRAINT `fk_po_prod` FOREIGN KEY (`product_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_po_bom` FOREIGN KEY (`bom_id`) REFERENCES `bom_header` (`bom_id`),
  CONSTRAINT `fk_po_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `fk_po_emp` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 9. PRODUCTION PLANNING
-- =========================================
CREATE TABLE IF NOT EXISTS `production_planning` (
  `plan_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `scheduled_start` DATETIME DEFAULT NULL,
  `scheduled_end` DATETIME DEFAULT NULL,
  `workstation` VARCHAR(50) DEFAULT NULL,
  `assigned_to` INT(11) DEFAULT NULL,
  `shift` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`plan_id`),
  KEY `fk_plan_po` (`po_id`),
  KEY `fk_plan_emp` (`assigned_to`),
  CONSTRAINT `fk_plan_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_emp` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 10. MATERIAL REQUIREMENT
-- =========================================
CREATE TABLE IF NOT EXISTS `material_requirement` (
  `req_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `material_id` INT(11) NOT NULL,
  `required_qty` DECIMAL(12,4) NOT NULL,
  `available_qty` DECIMAL(12,4) DEFAULT 0,
  `shortage_qty` DECIMAL(12,4) DEFAULT 0,
  `uom` VARCHAR(20) NOT NULL,
  `computed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`req_id`),
  KEY `fk_mr_po` (`po_id`),
  KEY `fk_mr_mat` (`material_id`),
  CONSTRAINT `fk_mr_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_mat` FOREIGN KEY (`material_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 11. MATERIAL ISSUANCE
-- =========================================
CREATE TABLE IF NOT EXISTS `material_issuance` (
  `issuance_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `material_id` INT(11) NOT NULL,
  `qty_issued` DECIMAL(12,4) NOT NULL,
  `uom` VARCHAR(20) NOT NULL,
  `lot_no` VARCHAR(50) DEFAULT NULL,
  `issued_by` INT(11) DEFAULT NULL,
  `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`issuance_id`),
  KEY `fk_mi_po` (`po_id`),
  KEY `fk_mi_mat` (`material_id`),
  KEY `fk_mi_emp` (`issued_by`),
  CONSTRAINT `fk_mi_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mi_mat` FOREIGN KEY (`material_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_mi_emp` FOREIGN KEY (`issued_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 12. PRODUCTION MONITORING
-- =========================================
CREATE TABLE IF NOT EXISTS `production_monitoring` (
  `monitor_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `stage` ENUM('Planned','Blending','Bottling','Labeling','Completed') NOT NULL,
  `started_at` DATETIME DEFAULT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `operator` INT(11) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  PRIMARY KEY (`monitor_id`),
  KEY `fk_pm_po` (`po_id`),
  KEY `fk_pm_emp` (`operator`),
  CONSTRAINT `fk_pm_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_emp` FOREIGN KEY (`operator`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 13. FINISHED GOODS OUTPUT
-- =========================================
CREATE TABLE IF NOT EXISTS `finished_goods_output` (
  `fg_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `qty_produced` DECIMAL(12,2) NOT NULL,
  `uom` VARCHAR(20) DEFAULT 'bottles',
  `lot_no` VARCHAR(50) DEFAULT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `received_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fg_id`),
  KEY `fk_fg_po` (`po_id`),
  KEY `fk_fg_prod` (`product_id`),
  KEY `fk_fg_wh` (`warehouse_id`),
  CONSTRAINT `fk_fg_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`),
  CONSTRAINT `fk_fg_prod` FOREIGN KEY (`product_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_fg_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 14. PRODUCTION COSTING
-- =========================================
CREATE TABLE IF NOT EXISTS `production_costing` (
  `cost_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `material_cost` DECIMAL(12,2) DEFAULT 0,
  `labor_cost` DECIMAL(12,2) DEFAULT 0,
  `overhead_cost` DECIMAL(12,2) DEFAULT 0,
  `excise_tax` DECIMAL(12,2) DEFAULT 0,
  `total_cost` DECIMAL(14,2) GENERATED ALWAYS AS
    (`material_cost` + `labor_cost` + `overhead_cost` + `excise_tax`) STORED,
  `cost_per_unit` DECIMAL(12,4) DEFAULT 0,
  `computed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cost_id`),
  UNIQUE KEY `uq_cost_po` (`po_id`),
  CONSTRAINT `fk_cost_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;











Inventory (the hub)


-- =========================================
-- 15. STOCK
-- =========================================
CREATE TABLE IF NOT EXISTS `stock` (
  `stock_id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `lot_no` VARCHAR(50) DEFAULT 'DEFAULT',
  `qty_on_hand` DECIMAL(14,4) DEFAULT 0,
  `qty_reserved` DECIMAL(14,4) DEFAULT 0,
  `qty_available` DECIMAL(14,4) GENERATED ALWAYS AS (`qty_on_hand` - `qty_reserved`) STORED,
  `unit_cost` DECIMAL(12,4) DEFAULT 0,
  `expiry_date` DATE DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`stock_id`),
  UNIQUE KEY `uq_stock` (`item_id`,`warehouse_id`,`lot_no`),
  KEY `fk_stock_item` (`item_id`),
  KEY `fk_stock_wh` (`warehouse_id`),
  CONSTRAINT `fk_stock_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_stock_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 16. STOCK MOVEMENTS (append-only audit)
-- =========================================
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) NOT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `movement_type` ENUM('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT(11) DEFAULT NULL,
  `qty` DECIMAL(14,4) NOT NULL,
  `uom` VARCHAR(20) DEFAULT NULL,
  `lot_no` VARCHAR(50) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_sm_item` (`item_id`),
  KEY `idx_sm_ref` (`reference_type`,`reference_id`),
  KEY `fk_sm_wh` (`warehouse_id`),
  KEY `fk_sm_emp` (`created_by`),
  CONSTRAINT `fk_sm_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_sm_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `fk_sm_emp` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 17. STOCK TRANSFERS
-- =========================================
CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `transfer_id` INT(11) NOT NULL AUTO_INCREMENT,
  `transfer_code` VARCHAR(20) NOT NULL,
  `from_warehouse` INT(11) NOT NULL,
  `to_warehouse` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `qty` DECIMAL(14,4) NOT NULL,
  `uom` VARCHAR(20) DEFAULT NULL,
  `lot_no` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('Pending','In Transit','Completed','Cancelled') DEFAULT 'Pending',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`transfer_id`),
  UNIQUE KEY `transfer_code` (`transfer_code`),
  KEY `fk_st_from` (`from_warehouse`),
  KEY `fk_st_to` (`to_warehouse`),
  KEY `fk_st_item` (`item_id`),
  KEY `fk_st_emp` (`created_by`),
  CONSTRAINT `fk_st_from` FOREIGN KEY (`from_warehouse`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `fk_st_to` FOREIGN KEY (`to_warehouse`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `fk_st_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_st_emp` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 18. STOCK ADJUSTMENTS
-- =========================================
CREATE TABLE IF NOT EXISTS `stock_adjustments` (
  `adjustment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `adjustment_code` VARCHAR(20) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `qty_change` DECIMAL(14,4) NOT NULL,
  `reason` ENUM('Breakage','Spillage','Expiry','Recount','Theft','Other') NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`adjustment_id`),
  UNIQUE KEY `adjustment_code` (`adjustment_code`),
  KEY `fk_adj_item` (`item_id`),
  KEY `fk_adj_wh` (`warehouse_id`),
  KEY `fk_adj_emp` (`approved_by`),
  CONSTRAINT `fk_adj_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_adj_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `fk_adj_emp` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;






Procurement






-- =========================================
-- 19. PURCHASE ORDERS
-- =========================================
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `po_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_code` VARCHAR(20) NOT NULL,
  `supplier_id` INT(11) NOT NULL,
  `order_date` DATE NOT NULL,
  `expected_date` DATE DEFAULT NULL,
  `status` ENUM('Draft','Sent','Partial','Received','Cancelled') DEFAULT 'Draft',
  `subtotal` DECIMAL(14,2) DEFAULT 0,
  `tax_amount` DECIMAL(14,2) DEFAULT 0,
  `total_amount` DECIMAL(14,2) DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`po_id`),
  UNIQUE KEY `po_code` (`po_code`),
  KEY `fk_purch_supp` (`supplier_id`),
  KEY `fk_purch_emp` (`created_by`),
  CONSTRAINT `fk_purch_supp` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  CONSTRAINT `fk_purch_emp` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `po_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `po_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `qty_ordered` DECIMAL(14,4) NOT NULL,
  `qty_received` DECIMAL(14,4) DEFAULT 0,
  `uom` VARCHAR(20) DEFAULT NULL,
  `unit_price` DECIMAL(12,4) NOT NULL,
  `line_total` DECIMAL(14,2) GENERATED ALWAYS AS (`qty_ordered` * `unit_price`) STORED,
  PRIMARY KEY (`po_item_id`),
  KEY `fk_poi_po` (`po_id`),
  KEY `fk_poi_item` (`item_id`),
  CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 20. GOODS RECEIPTS (Procurement → Inventory bridge)
-- =========================================
CREATE TABLE IF NOT EXISTS `goods_receipts` (
  `gr_id` INT(11) NOT NULL AUTO_INCREMENT,
  `gr_code` VARCHAR(20) NOT NULL,
  `po_id` INT(11) NOT NULL,
  `received_date` DATE NOT NULL,
  `received_by` INT(11) DEFAULT NULL,
  `warehouse_id` INT(11) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gr_id`),
  UNIQUE KEY `gr_code` (`gr_code`),
  KEY `fk_gr_po` (`po_id`),
  KEY `fk_gr_emp` (`received_by`),
  KEY `fk_gr_wh` (`warehouse_id`),
  CONSTRAINT `fk_gr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`),
  CONSTRAINT `fk_gr_emp` FOREIGN KEY (`received_by`) REFERENCES `employees` (`employee_id`),
  CONSTRAINT `fk_gr_wh` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
  `gr_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `gr_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `qty_received` DECIMAL(14,4) NOT NULL,
  `uom` VARCHAR(20) DEFAULT NULL,
  `lot_no` VARCHAR(50) DEFAULT NULL,
  `unit_cost` DECIMAL(12,4) NOT NULL,
  PRIMARY KEY (`gr_item_id`),
  KEY `fk_gri_gr` (`gr_id`),
  KEY `fk_gri_item` (`item_id`),
  CONSTRAINT `fk_gri_gr` FOREIGN KEY (`gr_id`) REFERENCES `goods_receipts` (`gr_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gri_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;









Sales & E-commerce


-- =========================================
-- 21. CART
-- =========================================
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(11) DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('Active','Converted','Abandoned','Expired') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `fk_cart_cust` (`customer_id`),
  CONSTRAINT `fk_cart_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cart_item` (
  `cart_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cart_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `qty` INT(11) NOT NULL,
  `unit_price_snapshot` DECIMAL(12,2) NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  KEY `fk_ci_cart` (`cart_id`),
  KEY `fk_ci_item` (`item_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 22. ORDERS
-- =========================================
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(20) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending','Paid','Processing','Shipped','Delivered','Cancelled','Refunded') DEFAULT 'Pending',
  `subtotal` DECIMAL(14,2) DEFAULT 0,
  `discount_total` DECIMAL(14,2) DEFAULT 0,
  `grand_total` DECIMAL(14,2) DEFAULT 0,
  `is_age_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `fk_ord_cust` (`customer_id`),
  CONSTRAINT `fk_ord_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `qty` INT(11) NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `excise_tax` DECIMAL(12,2) DEFAULT 0,
  `line_total` DECIMAL(14,2) NOT NULL,
  `cost_per_unit` DECIMAL(12,4) DEFAULT 0,
  PRIMARY KEY (`order_item_id`),
  KEY `fk_oi_ord` (`order_id`),
  KEY `fk_oi_item` (`item_id`),
  CONSTRAINT `fk_oi_ord` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 23. PAYMENTS
-- =========================================
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `payment_method` ENUM('Cash','Card','GCash','Maya','Bank Transfer','COD') NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `reference_no` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `fk_pay_ord` (`order_id`),
  CONSTRAINT `fk_pay_ord` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;






Accounting

-- =========================================
-- 25. CHART OF ACCOUNTS
-- =========================================
CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `account_id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_code` VARCHAR(20) NOT NULL,
  `account_name` VARCHAR(100) NOT NULL,
  `account_type` ENUM('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 26. JOURNAL ENTRIES
-- =========================================
CREATE TABLE IF NOT EXISTS `journal_entries` (
  `entry_id` INT(11) NOT NULL AUTO_INCREMENT,
  `entry_code` VARCHAR(20) NOT NULL,
  `entry_date` DATE NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `entry_code` (`entry_code`),
  KEY `fk_je_emp` (`created_by`),
  CONSTRAINT `fk_je_emp` FOREIGN KEY (`created_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
  `line_id` INT(11) NOT NULL AUTO_INCREMENT,
  `entry_id` INT(11) NOT NULL,
  `account_id` INT(11) NOT NULL,
  `debit` DECIMAL(14,2) DEFAULT 0,
  `credit` DECIMAL(14,2) DEFAULT 0,
  `remarks` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`line_id`),
  KEY `fk_jel_entry` (`entry_id`),
  KEY `fk_jel_acc` (`account_id`),
  CONSTRAINT `fk_jel_entry` FOREIGN KEY (`entry_id`) REFERENCES `journal_entries` (`entry_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jel_acc` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================
-- 27. PRODUCTION REPORTS (snapshots)
-- =========================================
CREATE TABLE IF NOT EXISTS `production_reports` (
  `report_id` INT(11) NOT NULL AUTO_INCREMENT,
  `report_type` ENUM('Per Product','Per Batch','Cost Summary') NOT NULL,
  `po_id` INT(11) DEFAULT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `period_start` DATE DEFAULT NULL,
  `period_end` DATE DEFAULT NULL,
  `payload` LONGTEXT DEFAULT NULL,
  `generated_by` INT(11) DEFAULT NULL,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `fk_rpt_po` (`po_id`),
  KEY `fk_rpt_prod` (`product_id`),
  KEY `fk_rpt_emp` (`generated_by`),
  CONSTRAINT `fk_rpt_po` FOREIGN KEY (`po_id`) REFERENCES `production_orders` (`po_id`),
  CONSTRAINT `fk_rpt_prod` FOREIGN KEY (`product_id`) REFERENCES `items` (`item_id`),
  CONSTRAINT `fk_rpt_emp` FOREIGN KEY (`generated_by`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

