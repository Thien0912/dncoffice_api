-- Migration: Thêm cột ref để link hrm_minh_chung về bảng gốc (bang_cap, chung_chi)
-- Chạy 1 lần để thêm cột

ALTER TABLE `hrm_minh_chung`
  ADD COLUMN `ref_table` varchar(100) DEFAULT NULL COMMENT 'Bảng nguồn: hrm_nhan_vien_bang_cap, hrm_nhan_vien_chung_chi, ...' AFTER `file_size`,
  ADD COLUMN `ref_id` int DEFAULT NULL COMMENT 'ID bản ghi nguồn (id_bang_cap, id_chung_chi, ...)' AFTER `ref_table`;

-- Index để query nhanh theo ref
ALTER TABLE `hrm_minh_chung`
  ADD KEY `idx_ref` (`ref_table`, `ref_id`);
