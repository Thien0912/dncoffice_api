-- Add locked_dates column to store locked date ranges as JSON
-- Format: [{"start": "YYYY-MM-DD", "end": "YYYY-MM-DD"}, ...]
ALTER TABLE `hrm_bang_cham_cong_thang`
ADD COLUMN `locked_dates` TEXT NULL COMMENT 'JSON array of locked date ranges' AFTER `trang_thai`;
