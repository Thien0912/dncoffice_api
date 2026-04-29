<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Validate
{
    protected $CI;
    protected $errors = [];
    protected $language = 'english';

    public function __construct()
    {
        // Lấy instance của CodeIgniter
        $this->CI = &get_instance();
        // Tải form_validation library nếu cần
        $this->CI->load->library('form_validation');
        $this->CI->load->helper('language');
        $this->initLanguage();
    }

    public function setLanguage($lang)
    {
        $this->language = $lang;
    }

    public function initLanguage()
    {
        $array_lang  = [
            'en' => 'english',
            'vi' => 'vietnamese'
        ];
        $langHeader = $this->CI->input->get_request_header('myoffice-language', TRUE); // Get lang from header
        if ($langHeader) {
            $this->language = $array_lang[$langHeader];
        }
    }
    /**
     * Hàm getRules: nạp file rule tuỳ theo module (VD: 'employee'), 
     * sau đó trả về rule dựa vào action (VD: 'create', 'update')
     *
     * @param string $module  Tên module (employee, contract, …)
     * @param string $action  Action (create, update, …)
     * @return array
     */
    public function getRules($module, $action)
    {
        $filePath = APPPATH . 'language/' . $this->language . '/' . $module . '_lang.php';
        if (file_exists($filePath))
            $this->CI->lang->load($module, $this->language);
        $rules = array();

        // Đường dẫn tới file rule. Ví dụ: application/validate/employee.php
        $filepath = APPPATH . 'validate/' . $module . '.php';

        if (file_exists($filepath)) {
            // Nạp file (bên trong có biến $config = array(...))
            require($filepath);

            // Nếu file có biến $config và là mảng
            if (isset($config) && is_array($config)) {
                // Lấy rule con dựa vào $action
                $rules = isset($config[$action]) ? $config[$action] : array();
            }
        }

        return $rules;
    }

    /**
     * Hàm setData: thiết lập dữ liệu cho form validation
     *
     * @param array $data  Dữ liệu cần validate
     */
    public function setData($data)
    {
        $this->CI->form_validation->set_data($data);
    }

    /**
     * Hàm setRulesAndRun: tiện ích gộp set_rules + run
     *
     * @param string $module  Tên module (employee, contract, …)
     * @param string $action  Action (create, update, …)
     * @return bool  Trả về true nếu validate thành công, ngược lại trả về false
     */
    public function setRulesAndRun($module, $action)
    {
        $filePath = APPPATH . 'language/' . $this->language . '/' . $module . '_lang.php';
        if (file_exists($filePath))
            $this->CI->lang->load($module, $this->language);
        // Lấy mảng rule
        $rules = $this->getRules($module, $action);
        // Gọi set_rules
        $this->CI->form_validation->set_rules($rules);

        // Trả về kết quả run
        if ($this->CI->form_validation->run()) {
            return true;
        } else {
            $this->renderError();
            return false;
        }
    }

    /**
     * Hàm errors: trả về mảng lỗi
     *
     * @return array  Mảng lỗi
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Hàm addError: thêm lỗi vào mảng lỗi
     *
     * @param string $field  Tên field bị lỗi
     * @param string $message  Thông báo lỗi
     */
    public function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Hàm renderError: lấy lỗi từ form validation và thêm vào mảng lỗi
     */
    public function renderError()
    {
        $errors_form = $this->CI->form_validation->error_array();
        foreach ($errors_form as $key => $value) {
            if (array_key_exists($key, $this->errors)) {
                if (!in_array($value, $this->errors[$key]))
                    $this->addError($key, $value);
            } else {
                $this->addError($key, $value);
            }
        }
    }

    // Nếu bạn muốn viết custom callback, có thể khai báo hàm ở đây,
    // Ví dụ: check email trùng DB, check ngày ...
}
