<?php
class Model_common extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    function check_authentication($substantiation)
    {
        if($substantiation == "")
        {
            log_message('error'," empty token authentication fails".implode('|||', $_REQUEST));
            respond_error_to_api_v1("Authentication fails");
        }
        $this->db->where('substantiation', $substantiation);
        $this->db->where('is_active', 1);
        $this->db->where('is_verified', 1);
        $res = $this->db->get('users')->row_array();
        if(empty($res))
        {
            log_message('error',"authentication fails".implode('|||', $_REQUEST));
            respond_error_to_api_v1("Authentication fails");
        } else {
            return $res;
        }
    }

	public function do_upload($file_name,$path,$type = 'gif|jpg|png|jpeg')
    {
        $config['upload_path']   = './'.$path.'/';
        $config['allowed_types'] = $type;
        $config['max_size']      = 0;
        $config['max_width']     = 0;
        $config['max_height']    = 0;
        $config['file_name']     = uniqid().".".pathinfo($_FILES[$file_name]['name'], PATHINFO_EXTENSION);

        if (!file_exists($config['upload_path'])) {
            if (!mkdir($config['upload_path'], 0777, true)) {
                log_message('error',"Failed to create folders...");
            }
        }

        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if (!$this->upload->do_upload($file_name)) {
            $resultArr['success'] = false;
            $resultArr['error']   = $this->upload->display_errors();
            respond_error_to_api_v1("Failed to file upload", $resultArr);
        }
        else
        {
            return $path.'/'.$config['file_name'];
        }
    }

    function get_data_from_excel($file_name)
    {
        $file = $file_name;
        // $file = './assets/sample.xlsx';
 
        //load the excel library
        $this->load->library('excel');
         
        //read file from path
        $objPHPExcel = PHPExcel_IOFactory::load($file);
         
        //get only the Cell Collection
        $cell_collection = $objPHPExcel->getActiveSheet()->getCellCollection();
         
        //extract to a PHP readable array format
        foreach ($cell_collection as $cell) {
            $column = $objPHPExcel->getActiveSheet()->getCell($cell)->getColumn();
            $row = $objPHPExcel->getActiveSheet()->getCell($cell)->getRow();
            $data_value = $objPHPExcel->getActiveSheet()->getCell($cell)->getValue();
         
            //The header will/should be in row 1 only. of course, this can be modified to suit your need.
            if ($row == 1) {
                $header[$row][$column] = $data_value;
            } else {
                $arr_data[$row][$column] = $data_value;
            }
        }
         
        //send the data in an array format
        // $data['header'] = $header;
        // $data['values'] = $arr_data;
        return $arr_data;
    }
    
    function send_email($to_email, $subject, $mail_body)
    {
    	$config = Array(
		    'protocol' => 'smtp',
		    'smtp_host' => SMTP_HOST,
		    'smtp_port' => 587,
		    'smtp_user' => SMTP_USER,
		    'smtp_pass' => SMTP_PASSWORD,
		    'mailtype'  => 'html', 
		    'charset'   => 'iso-8859-1',
    		'wordwrap' => TRUE
		);
		$this->load->library('email', $config);
        
        // $from = $this->config->item('smtp_user');

        $this->email->set_newline("\r\n");
        $this->email->from(FROM_EMAIL);
        $this->email->to($to_email);
        $this->email->subject($subject);
        $this->email->message($mail_body);

        if ($this->email->send()) {
            return true;
        } else {
            show_error($this->email->print_debugger());
            log_message('error', show_error($this->email->print_debugger()));
        }
    }

    function get_all_social_type()
    {
        $this->db->where('status',1);
        $this->db->where('is_custom',0);
        $this->db->order_by("type_order", "asc");
    	return $this->db->get('social_media_type');
    }

    public function check_login($username, $password)
    {
        try {
            $this->db->where('user_name', $username);
            $this->db->where('password', md5($password));
            $maintain = $this->db->get('admin_user');
            log_message("error", $this->db->last_query());
            return $maintain;
        } catch (Exception $e) {
            log_message("error",$e);
        }
    }

    public function admin_do_upload($path, $file_name, $type)
    {
        $config['upload_path']   = $path;
        $config['file_name']     = $file_name;
        $config['allowed_types'] = $type;
        // $config['max_size']             = 100;
        // $config['max_width']            = 1024;
        // $config['max_height']           = 768;

        $this->load->library('upload', $config);
        if (!file_exists($config['upload_path'])) {
            if (!mkdir($config['upload_path'], 0777, true)) {
                log_message('error',"Failed to create folders...");
            }
        }
        if ( ! $this->upload->do_upload('userfile'))
        {
            $error = array('error' => $this->upload->display_errors());
            log_message("error",$error['error']);
            return $error;
        }
        else
        {
            $data = array('upload_data' => $this->upload->data());
            return $data;
        }
    }

    function checkUser($pass, $id)
    {
        $this->db->where('id', $id);
        $this->db->where('password', $pass);
        $res = $this->db->get('admin_user');
        return $res;
    }

    function update_user_pass($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update('admin_user', $data);
    }
}