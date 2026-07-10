<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{
    protected $table = 'mst_user';

    public function get_by_username($username)
    {
        return $this->db->get_where($this->table, array(
            'username'  => $username,
            'is_active' => TRUE,
        ))->row_array();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, array('id' => $id))->row_array();
    }

    /**
     * Verifikasi login. Return data user (tanpa password) kalau cocok, FALSE kalau tidak.
     */
    public function verify_login($username, $password)
    {
        $user = $this->get_by_username($username);

        if (!$user) {
            return FALSE;
        }

        if (!password_verify($password, $user['password'])) {
            return FALSE;
        }

        unset($user['password']);
        return $user;
    }

    public function update_last_login($id)
    {
        $this->db->where('id', $id)->update($this->table, array('last_login' => date('Y-m-d H:i:s')));
    }

    public function get_all()
    {
        return $this->db->order_by('username', 'ASC')->get($this->table)->result_array();
    }

    /**
     * Cek keberadaan username tanpa peduli status aktif (dipakai untuk validasi unik).
     */
    public function find_by_username_any_status($username)
    {
        return $this->db->get_where($this->table, array('username' => $username))->row_array();
    }

    public function create($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete($this->table);
    }
}
