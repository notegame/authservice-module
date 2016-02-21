<?php namespace Modules\AuthService\Entities;

use Illuminate\Database\Eloquent\Model;
use Activation;
use Carbon\Carbon;

class UserActivation extends Model
{

	public static function getExpires()
	{
		return env("ACTIVATE_CODE_EXPIRE",60); // default 60 min
	}

	public function scopeNoExpires($query)
	{
		return $query
		->where('created_at', '>', Carbon::now()->subMinutes(self::getExpires()))
		->where('completed', false);
	}

	public static function activate($ref_id, $code)
	{

		try {
			$activation = Self::where("ref_id",$ref_id)
			->where("code",$code)
			->noExpires()
			->firstOrFail();
		} catch (\Exception $e) {
			throw new \Exception(trans("authservice::exception.not_found_activate_code"));
			return false;
		}

		$activation->completed = 1;
		$activation->completed_at = Carbon::now();
		$activation->save();

		$user = $activation->getUser();

		if($sentinel_activation = Activation::completed($user))
		{
			
		}else{
			$sentinel_activation = Activation::create($user)->code;
			Activation::complete($user,$sentinel_activation);
		}
		
		return $activation;
	}

	public function getUser()
	{
		return $this->user()->first();
	}

	public function user()
	{
		return $this->belongsTo("User");
	}
}