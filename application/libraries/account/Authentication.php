<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Authentication {

	var $CI;

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Obtain a reference to the ci super object
		$this->CI =& get_instance();
		
		//Load the session, if CI2 load it as library, if it is CI3 load as a driver
		if (substr(CI_VERSION, 0, 1) == '2')
		{
			$this->CI->load->library('session');
		}
		else
		{
			$this->CI->load->driver('session');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Check user signin status
	 *
	 * @access public
	 * @return bool
	 */
	function is_signed_in()
	{
		return $this->CI->session->userdata('account_id') ? TRUE : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Sign user in
	 *
	 * @access public
	 * @param int  $account_id
	 * @param bool $remember
	 * @return void
	 */
	function sign_in($username, $password, $remember = FALSE)
	{
		// Get user by username / email
		if ( ! $user = $this->CI->account_model->get_by_username_email($username))
		{
			return FALSE;
		}
		else
		{
			// Check password
			if ( ! $this->check_password($user->password, $password))
			{
				// Increment sign in failed attempts
				$this->CI->session->set_userdata('sign_in_failed_attempts', (int)$this->CI->session->userdata('sign_in_failed_attempts') + 1);
				
				return FALSE;
			}
			else
			{
				// Clear sign in fail counter
				$this->CI->session->unset_userdata('sign_in_failed_attempts');
				
				//Due to new functionality in CI3 remember me feature is temporarily disabled
				//$remember ? $this->CI->session->cookie_monster(TRUE) : $this->CI->session->cookie_monster(FALSE);
				
				$this->CI->session->set_userdata('account_id', $user->id);
				
				$this->CI->load->model('account/account_model');
				
				$this->CI->account_model->update_last_signed_in_datetime($user->id);
				
				// Redirect signed in user with session redirect
				if ($redirect = $this->CI->session->userdata('sign_in_redirect'))
				{
					$this->CI->session->unset_userdata('sign_in_redirect');
					redirect($redirect);
				}
				// Redirect signed in user with GET continue
				elseif ($this->CI->input->get('continue'))
				{
					redirect($this->CI->input->get('continue'));
				}
				
				redirect('');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Sign user out
	 *
	 * @access public
	 * @return void
	 */
	function sign_out()
	{
		$this->CI->session->unset_userdata('account_id');
		
		redirect('');
	}

	// --------------------------------------------------------------------

	/**
	 * Check password validity
	 *
	 * @access public
	 * @param object $account
	 * @param string $password
	 * @return bool
	 */
	private function check_password($password_hash, $password)
	{
		$this->CI->load->helper('account/phpass');

		$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);

		return $hasher->CheckPassword($password, $password_hash) ? TRUE : FALSE;
	}

}


/* End of file Authentication.php */
/* Location: ./application/account/libraries/Authentication.php */