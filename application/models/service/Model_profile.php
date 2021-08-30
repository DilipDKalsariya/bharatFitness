<?php
class Model_profile extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }
 
  function get_users()
  {
    $res = $this->db->get('tbl_users');
    return $res;
  }

  function add_profile($data)
  {
    $this->db->insert("user_profile", $data);
    $return = $this->db->insert_id();
    if($data['is_active'] == 1)
    {
      $up['is_active'] = 0;
      $this->db->where('user_id', $data['user_id']);
      $this->db->where('id !=',$return);
      $this->db->update('user_profile', $up);
    }
    return $return;
  } 

  function update_profile($data, $profile_id)
  {
    $this->db->where("id", $profile_id);
    $res = $this->db->update("user_profile", $data);
    
    return $res;
  }

  function active_profile($data, $profile_id)
  {
    if(array_key_exists('is_deactive', $data) && $data['is_deactive'] == 1)
    {
      $up['is_active'] = 0;  
    } else {
      $up['is_active'] = 1;
    }
    $up1['is_active'] = 0;
    $this->db->where("id", $profile_id);
    $res = $this->db->update("user_profile", $up);
    
    $this->db->where('user_id', $data['user_id']);
    $this->db->where('id !=',$profile_id);
    $this->db->update('user_profile', $up1);
    
    return $res;
  }

  function get_profile($id)
  {
    $this->db->where("id", $id);
    $res = $this->db->get("user_profile");
    return $res;
  }
  
  function get_all_profile($id)
  {
    $this->db->where("user_id", $id);
    $res = $this->db->get("user_profile");
    return $res;
  }

  function save_profiles_link($data)
  {
    if(array_key_exists("link_id", $data))
    {
      $id = $data['link_id'];
      unset($data['link_id']);
      $this->db->where("id", $id);
      return $this->db->update('social_media_links', $data);
    } else {
      $this->db->insert("social_media_links", $data);
      return $this->db->insert_id();
    }
  }

  function save_master_type($data)
  {
    $this->db->insert('social_media_type', $data);
    return $this->db->insert_id();
  }


  function get_master_type($link_id)
  {
    $this->db->where('id', $link_id);
    $res = $this->db->get('social_media_links')->row_array();
    return $res['type_id'];
  }

  function update_type_master($type_id, $mst)
  {
    $this->db->where('id',$type_id);
    $this->db->update('social_media_type', $mst);
  }

  function delete_link($id)
  {
    $this->db->select('mst.id as type_id');
    $this->db->join('social_media_type as mst','mst.id = social_media_links.type_id','left');
    $this->db->where('social_media_links.id',$id);
    $this->db->where('mst.is_custom',1);
    $res = $this->db->get('social_media_links')->row_array();
    if(!empty($res))
    {
      $this->db->where("id", $res['type_id']);
      $this->db->delete('social_media_type');
    }

    $this->db->where("id", $id);
    return $this->db->delete('social_media_links');
  }

  function get_profile_detail($id)
  {
    $this->db->select('sml.*, smt.social_media_name, smt.type_order, IF(smt.social_media_logo = "","", CONCAT("'.BASE_URL.'",smt.social_media_logo) ) as social_media_logo, smt.type');
    $this->db->join("social_media_type smt","sml.type_id = smt.id", "left");
    $this->db->where('profile_id', $id);
    $this->db->order_by('sml.social_order','asc');
    $res = $this->db->get("social_media_links sml");
    return $res;
  }
  
  function update_link_order($data)
  {
    //   print_r($data);
    $this->db->update_batch("social_media_links",$data,"id");
    log_message('error', 'last query------------>'. $this->db->last_query());
  }

  function delete_profile($id)
  {
    $this->db->where('profile_id',$id);
    $this->db->delete("social_media_links");

    $this->db->where('id',$id);
    return $this->db->delete("user_profile"); 
  }
}