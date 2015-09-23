<?php namespace Teepluss\Gateway\Drivers\TruePaymentApi;

class RC4 {

	public static function EncryptRC4($pwd, $data)
	{
		$key[] = '';
		$box[] = '';
		$pwd_length = strlen($pwd);
		$data_length = strlen($data);
		for ($i = 0; $i < 256; $i++)
		{
			$key[$i] = ord($pwd[$i % $pwd_length]);
			$box[$i] = $i;
		}
		for ($j = $i = 0; $i < 256; $i++)
		{
			$j = ($j + $box[$i] + $key[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for ($a = $j = $i = 0; $i < $data_length; $i++)
		{
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$k = $box[(($box[$a] + $box[$j]) % 256)];
			((strlen(dechex(ord($data[$i]) ^ $k)) == 1) ? $Zero = "0" : $Zero = "");
			@$cipher = $cipher . $Zero . dechex(ord($data[$i]) ^ $k);
		}

		return $cipher;
	}

	public static function DecryptRC4($key, $data)
	{
		return static::ASC2CHR(static::EncryptRC4($key, static::ASC2CHR($data)));
	}

	private static function ASC2CHR($inp)
	{
		$tempChar = '';
		$partStr  = '';

		while (strlen($inp) > 1)
		{
			$tempChar = substr($inp, 0, 2);
			$inp = substr($inp, 2, (strlen($inp) - 2));
			$partStr = $partStr . chr(hexdec($tempChar));
		}

		return $partStr;
	}

}