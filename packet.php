<?php 

class Packet {

	public $bytes;

	public function __construct($string)
	{
		$this->bytes = array_values(unpack('C*', $string));
  	}

  	public function log() {
  	  file_put_contents(
  	  	"info.log",
  	  	"Fin ".$this->getFin()."\n".
  	  	"Rsv1 ".$this->getRsv1()."\n".
  	  	"Rsv2 ".$this->getRsv2()."\n".
  	  	"Rsv3 ".$this->getRsv3()."\n".
  	  	"Opcode ".$this->getOpcode()."\n".
  	  	"isMasked ".$this->isMasked()."\n".
  	  	"PayloadLength ".$this->getPayloadLength()."\n".
  	  	"MaskOffset ".$this->getMaskOffset()."\n".
  	  	"PayloadOffset ".$this->getPayloadOffset()."\n"
  	  );
  	  file_put_contents("bytes.log", json_encode($this->getBytes(), true));
      file_put_contents("mask.log", json_encode($this->getMask(), true));
      file_put_contents("payload.log", json_encode($this->getPayload(), true));
  	}

  	public function getBytes() {
  		return $this->bytes;
  	}

  	public function getBit($index, $number)
	{
	  	return ($number >> $index) & 1;
	}

	public function getFin() {
	  	return $this->getBit(7, $this->bytes[0]);
	}

	public function getRsv1() {
		return $this->getBit(6, $this->bytes[0]);
	}

	public function getRsv2() {
		return $this->getBit(5, $this->bytes[0]);
	}

	public function getRsv3() {
		return $this->getBit(4, $this->bytes[0]);
	}

	public function getOpcode() {
		return $this->bytes[0] & 0b00001111;
	} 

	public function isMasked() {
		return $this->getBit(7, $this->bytes[1]);
	} 

	public function getPayloadTempLength() {
		$length = $this->bytes[1] & 0b01111111;
		return $length;
	}

	public function getPayloadLength() {
		$length = $this->getPayloadTempLength();
		if($length == 126)
		{
			$int16 = pack("C*", $this->bytes[2],$this->bytes[3]);
			$length = unpack("n", $int16)[1];
		}
		else if($length == 127)
		{
			$int64 = pack("C*", $this->bytes[2],$this->bytes[3],$this->bytes[4],$this->bytes[5],$this->bytes[6],$this->bytes[7],$this->bytes[8],$this->bytes[9]);
			$length = unpack("Q", $int64)[1];
		}
		return $length;
	}

	public function getMaskOffset() {
		$length = $this->getPayloadTempLength();
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