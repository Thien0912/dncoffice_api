<?php
class Pxl
{
  function __construct()
  {
    require_once APPPATH . '/libraries/pxl/PHPExcel.php';
    require_once APPPATH . '/libraries/pxl/PHPExcel/IOFactory.php';
  }
  /*
  *
  * Tạo hàm chung để điền dữ liệu vào Excel, lấy ra dữ liệu theo mảng từ trong excel
  * Các hàm bên dưới là tự tạo không phải thư viện có sẵn
  *
  */
  /*
  * FUNCTION importExcel()
  *   IMPORT DỮ LIỆU
  *   $numRowHeading => là hàng chứa tên cột trong csdl
  *   $numRow_start_content => là hàng bắt đầu có chứa dữ liệu cần import
  *   $path => đường dẫn của file
  */
  public function importExcel($numRowHeading, $numRow_start_content, $path)
  {
    $objPHPExcel = new PHPExcel();
    // Kiểm tra định dạng file
    $fileType = PHPExcel_IOFactory::identify($path);
    $allowedTypes = ['Excel5', 'Excel2007', 'CSV'];
    if (!in_array($fileType, $allowedTypes)) {
      echo "File không đúng định dạng!";
      return;
    }
    $objPHPExcel = PHPExcel_IOFactory::load($path);
    $sheet = $objPHPExcel->getActiveSheet();
    //hàng chứa dữ liệu là tên cột trong csdl
    $header = $sheet->rangeToArray('A' . $numRowHeading . ':' . $sheet->getHighestColumn() . $numRowHeading)[0];

    // Lặp qua các hàng dữ liệu
    $dataList = [];
    foreach ($sheet->getRowIterator($numRow_start_content) as $row) {
      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(FALSE); // Lặp qua tất cả các ô

      $rowData = [];
      foreach ($cellIterator as $cell) {
        $rowData[] = $cell->getValue();
      }

      // Kết hợp tiêu đề với dữ liệu
      $combined = array_combine($header, $rowData);
      $filtered = array_filter($combined, function ($value, $key) {
        return !empty($key);
      }, ARRAY_FILTER_USE_BOTH);

      $dataList[] = $filtered;
    }
    return $dataList;
  }
  /*
  * Trả về file excel từ dữ liệu đã import và cột hiển thị kết quả import
  * $data => mảng nội dung trong file excel
  * $heading => mảng tiêu đề
  * $rowIndex => dòng bắt đầu gán nội dung (dòng tiêu đề + 1)
  * $exportConfig => Nội dung custom trong file excel
  */
  public function returnExcel_from_import($data, $heading = [], $exportConfig = [])
  {
    $objPHPExcel = new PHPExcel();

    // Đặt các thuộc tính tài liệu
    $objPHPExcel->getProperties()
      ->setTitle(isset($exportConfig['title']) ? $exportConfig['title'] : 'Unknown')
      ->setDescription(isset($exportConfig['description']) ? $exportConfig['description'] : 'Unknown');

    // Đặt tiêu đề vào dòng
    $objPHPExcel->setActiveSheetIndex(0);
    $this->fillExcelSheet($objPHPExcel->getActiveSheet(), $heading['heading'], $heading['rowHeading']);
    $highestColumn = $objPHPExcel->getActiveSheet()->getHighestColumn(); // Lấy ký tự chữ cái cột cuối cùng

    $objPHPExcel->getActiveSheet()->getStyle('A' . $heading['rowHeading'] . ':' . $highestColumn . $heading['rowHeading'])
      ->applyFromArray([
        'alignment' => [
          'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
          'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ],
        'font' => [
          'bold' => true, // Làm đậm tiêu đề
          'name' => 'Times New Roman', // Đặt font là Times New Roman
          'size' => 12, // Đặt cỡ chữ là 12
        ],
      ]);
    // Lấy dữ liệu và đặt vào các dòng tiếp theo 
    $rowIndex = isset($exportConfig['rowContent_start']) ? $exportConfig['rowContent_start'] : 2;
    foreach ($data as $key => $cell) {
      $lastColumnIndex = count($cell);
      $lastColumnLetter = PHPExcel_Cell::stringFromColumnIndex($lastColumnIndex - 1);

      /*
          * Kiểm tra giá trị cột cuối cùng
          * Tô màu ô kết quả success = màu xanh, fail = màu đỏ
          */
      if (strtolower(end($cell)) == strtolower("Success")) {
        $color = isset($exportConfig['colorSuccess']) ? $exportConfig['colorSuccess'] : '77c884';
      } else {
        $color = isset($exportConfig['colorFail']) ? $exportConfig['colorFail'] : 'f57878';
      }
      $objPHPExcel->getActiveSheet()->getStyle($lastColumnLetter . $rowIndex)->applyFromArray([
        'fill' => [
          'type' => PHPExcel_Style_Fill::FILL_SOLID,
          'color' => ['rgb' => $color]
        ]
      ]);

      //Gán giá trị vào ô 
      $this->fillExcelSheet($objPHPExcel->getActiveSheet(), $cell, $rowIndex);
      $rowIndex++;
    }
    // Đặt tên worksheet
    $objPHPExcel->getActiveSheet()->setTitle(isset($exportConfig['sheetName']) ? $exportConfig['sheetName'] : 'Unknown'); //Đặt lại tên sheet

    // Đặt chỉ số trang tính hoạt động là trang đầu tiên
    $objPHPExcel->setActiveSheetIndex(0);

    // Đường dẫn và tên file bạn muốn lưu
    $directory = isset($exportConfig['directory']) ? $exportConfig['directory'] : 'Unknown'; // Thư mục để lưu file
    $filename = isset($exportConfig['filename']) ? $exportConfig['filename'] : 'Unknown'; // Tên file kèm timestamp để tránh trùng lặp
    $filePath = $directory . $filename . time() . '.xlsx';

    if (!is_dir($directory)) {
      mkdir($directory, 0755, true);
    }

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($filePath);
    return $filePath;
  }
  public function fillExcelSheet($sheet, $data, $startRow = 1)
  {
    $char = 'A';
    foreach ($data as $key => $value) {
      $cell = $char . $startRow;
      $sheet->setCellValue($cell, $value);
      $sheet->getColumnDimension($char)->setAutoSize(true);
      $sheet->getStyle($cell)->getFont()->setBold($startRow === 1); // In đậm nếu là hàng đầu tiên (tiêu đề) 
      $char++;
    }
  }
  public function fillColor($path, $columnIndex, $row, $color = 'FFFF00')
  {
    $objPHPExcel = PHPExcel_IOFactory::load($path);
    $sheet = $objPHPExcel->getActiveSheet(); // Lấy trang đang hoạt động

    // Lấy tên cột (A, B, C, ...)
    $column = PHPExcel_Cell::stringFromColumnIndex($columnIndex);

    $sheet->getStyle($column . $row)->getFill()->applyFromArray(
      [
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array('rgb' => $color) // Mã màu vàng
      ]
    );

    // Lưu lại file Excel đã chỉnh sửa
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($path);
  }
}
