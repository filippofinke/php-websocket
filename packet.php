<?php 

class Packet {

	public $bytes;

	public function __construct($string)
	{
		$this->bytes = array_values(unpack('C*', $string));
  	}

  	public function getBytes() {
  		return $this->bytes;
  	}

  	public function getBit($index, $number)
	{
	  	return ($number >> $index) & 1;
	}

	public function getFin() {
	  	return getBit(7, $this->bytes[0]);
	}

	public function getRsv1() {
		return getBit(6, $this->bytes[0]);
	}

	public function getRsv2() {
		return getBit(5, $this->bytes[0]);
	}

	public function getRsv3() {
		return getBit(4, $this->bytes[0]);
	}

	public function getOpcode() {
		return $this->bytes[0] & 0b00001111;
	} 

	public function isMasked() {
		return getBit(7, $this->bytes[1]);
	} 

	public function getPayloadLength() {
		$length = $this->bytes[1] & 0b01111111;
		/* If this value is between 0 and 125, then it is the length of message. If it is 126, the following 2 bytes (16-bit unsigned integer) are the length. If it is 127, the following 8 bytes (64-bit unsigned integer) are the length. */
		if($length == 126)
		{

		}
		else if($length == 127)
		{

		}
		return $length;
	}

	public function getMaskOffset() {
		$length = $this->getPayloadLength();
		$offset = 2;
		if($length == 126)
		{
			$offset += 2;
		}
		else if($length == 127)
		{
			$offset += 8;
		}
		return $offset;
	}

	public function getPayloadOffset() {
		return $this->getMaskOffset() + 4;
	}

	public function getMask() {
		$mask = array();
		$offset = $this->getMaskOffset();
		$mask[] = $this->bytes[$offset];
		$mask[] = $this->bytes[$offset + 1];
		$mask[] = $this->bytes[$offset + 2];
		$mask[] = $this->bytes[$offset + 3];
		return $mask;
	} 

	public function getPayload() {
		$payload = array_slice($this->bytes, $this->getPayloadOffset());
		return $payload;
	}

	public function getPayloadString() {
		$string = "";
		$key = $this->getMask();
		$payload = $this->getPayload();
		for($i = 0; $i < count($payload); $i++)
		{
			$string .= chr($payload[$i] ^ $key[$i % 4]);
		}
		return $string;
	}

}

?>