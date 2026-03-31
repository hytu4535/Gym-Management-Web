-- Migration: Add macro nutrients (protein, carbs, fat) percentages to nutrition_plans table
-- Date: 2026-03-31
-- Description: Add protein_percent, carbs_percent, fat_percent columns to track macronutrient ratios

ALTER TABLE nutrition_plans 
ADD COLUMN protein_percent DECIMAL(5,2) DEFAULT 30 COMMENT 'Tỷ lệ protein (%)',
ADD COLUMN carbs_percent DECIMAL(5,2) DEFAULT 45 COMMENT 'Tỷ lệ carbohydrate (%)',
ADD COLUMN fat_percent DECIMAL(5,2) DEFAULT 25 COMMENT 'Tỷ lệ chất béo (%)';

-- Add check constraint to ensure percentages sum to 100 (optional, database dependent)
-- Note: MySQL doesn't support CHECK constraints well, so validation should be done in application
