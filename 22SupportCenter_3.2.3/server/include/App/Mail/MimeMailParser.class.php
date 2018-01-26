<?php
define('MIME_PARSER_START',        1);
define('MIME_PARSER_HEADER',       2);
define('MIME_PARSER_HEADER_VALUE', 3);
define('MIME_PARSER_BODY',         4);
define('MIME_PARSER_BODY_START',   5);
define('MIME_PARSER_BODY_DATA',    6);
define('MIME_PARSER_BODY_DONE',    7);
define('MIME_PARSER_END',          8);

define('MIME_MESSAGE_START',            1);
define('MIME_MESSAGE_GET_HEADER_NAME',  2);
define('MIME_MESSAGE_GET_HEADER_VALUE', 3);
define('MIME_MESSAGE_GET_BODY',         4);
define('MIME_MESSAGE_GET_BODY_PART',    5);

define('MIME_ADDRESS_START',            1);
define('MIME_ADDRESS_FIRST',            2);

class App_Mail_MimeMailParser
{
	var $error='';
	var $error_position = -1;
	var $mbox = 0;
	var $decode_headers = 1;
	var $decode_bodies = 1;

	var $address_headers = array(
		'from:' => 1,
		'to:' => 1,
		'cc:' => 1,
		'bcc:' => 1
	);
	var $ignore_syntax_errors=1;
	var $warnings=array();

	var $state = MIME_PARSER_START;
	var $buffer = '';
	var $buffer_position = 0;
	var $offset = 0;
	var $parts = array();
	var $part_position = 0;
	var $headers = array();
	var $body_parser;
	var $body_parser_state = MIME_PARSER_BODY_DONE;
	var $body_buffer = '';
	var $body_buffer_position = 0;
	var $body_offset = 0;
	var $current_header = '';
	var $file;
	var $body_file;
	var $position = 0;
	var $body_part_number = 1;
	var $next_token = '';
	var $end_of_data;

	Function SetError($error)
	{
		$this->error = $error;
		return(0);
	}

	Function SetErrorWithContact($error)
	{
		return($this->SetError($error.'. Please contact the author Manuel Lemos <mlemos@acm.org> and send a copy of this message to let him add support for this kind of messages'));
	}

	Function SetPositionedError($error, $position)
	{
		$this->error_position = $position;
		return($this->SetError($error));
	}

	Function SetPositionedWarning($error, $position)
	{
		if(!$this->ignore_syntax_errors)
			return($this->SetPositionedError($error, $position));
		$this->warnings[$position]=$error;
		return(1);
	}

	Function SetPHPError($error, &$php_error_message)
	{
		if(IsSet($php_error_message)
		&& strlen($php_error_message))
			$error .= ': '.$php_error_message;
		return($this->SetError($error));
	}

	Function ResetParserState()
	{
		$this->error='';
		$this->error_position = -1;
		$this->state = MIME_PARSER_START;
		$this->buffer = '';
		$this->buffer_position = 0;
		$this->offset = 0;
		$this->parts = array();
		$this->part_position = 0;
		$this->headers = array();
		$this->body_parser_state = MIME_PARSER_BODY_DONE;
		$this->body_buffer = '';
		$this->body_buffer_position = 0;
		$this->body_offset = 0;
		$this->current_header = '';
		$this->position = 0;
		$this->body_part_number = 1;
		$this->next_token = '';
	}

	Function Tokenize($string,$separator="")
	{
		if(!strcmp($separator,""))
		{
			$separator=$string;
			$string=$this->next_token;
		}
		for($character=0;$character<strlen($separator);$character++)
		{
			if(GetType($position=strpos($string,$separator[$character]))=='integer')
				$found=(IsSet($found) ? min($found,$position) : $position);
		}
		if(IsSet($found))
		{
			$this->next_token=substr($string,$found+1);
			return(substr($string,0,$found));
		}
		else
		{
			$this->next_token='';
			return($string);
		}
	}

	Function ParseStructuredHeader($value, &$type, &$parameters, &$character_sets, &$languages)
	{
		$type = strtolower(trim($this->Tokenize($value, ';')));
		$p = trim($this->Tokenize(''));
		$parameters = $character_sets = $languages = array();
		while(strlen($p))
		{
			$parameter = trim(strtolower($this->Tokenize($p, '=')));
			$value = trim($this->Tokenize(';'));
			if(!strcmp($value[0], '"')
			&& !strcmp($value[strlen($value) - 1], '"'))
				$value = substr($value, 1, strlen($value) - 2);
			$p = trim($this->Tokenize(''));
			if(($l=strlen($parameter))
			&& !strcmp($parameter[$l - 1],'*'))
			{
				$parameter=$this->Tokenize($parameter, '*');
				if(IsSet($parameters[$parameter])
				&& IsSet($character_sets[$parameter]))
					$value = $parameters[$parameter] . UrlDecode($value);
				else
				{
					$character_sets[$parameter] = strtolower($this->Tokenize($value, '\''));
					$languages[$parameter] = $this->Tokenize('\'');
					$value = UrlDecode($this->Tokenize(''));
				}
			}
			$parameters[$parameter] = $value;
		}
	}

	Function FindStringLineBreak($string, $position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($string, $break="\n", $position))=='integer')
		{
			if($line_break>$position
			&& $string[$line_break-1]=="\r")
			{
				$line_break--;
				$break="\r\n";
			}
			return(1);
		}
		return(GetType($line_break=strpos($string, $break="\r", $position))=='integer');
	}

	Function FindLineBreak($position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($this->buffer, $break="\n", $position))=='integer')
		{
			if($line_break>$position
			&& $this->buffer[$line_break-1]=="\r")
			{
				$line_break--;
				$break="\r\n";
			}
			return(1);
		}
		return(GetType($line_break=strpos($this->buffer, $break="\r", $position))=='integer');
	}

	Function FindBodyLineBreak($position, &$break, &$line_break)
	{
		if(GetType($line_break=strpos($this->body_buffer, $break="\n", $position))=='integer')
		{
			if($line_break>$position
			&& $this->body_buffer[$line_break-1]=="\r")
			{
				$line_break--;
				$break="\r\n";
			}
			return(1);
		}
		return(GetType($line_break=strpos($this->body_buffer, $break="\r", $position))=='integer');
	}

	Function ParseHeaderString($body, &$position, &$headers)
	{
		$l = strlen($body);
		$headers = array();
		for(;$position < $l;)
		{
			if($this->FindStringLineBreak($body, $position, $break, $line_break))
			{
				$line = substr($body, $position, $line_break - $position);
				$position = $line_break + strlen($break);
			}
			else
			{
				$line = substr($body, $position);
				$position = $l;
			}
			if(strlen($line)==0)
				break;
			$h = strtolower(strtok($line,':'));
			$headers[$h] = trim(strtok(''));
		}
	}

	Function ParsePart($end, &$part, &$need_more_data)
	{
		$need_more_data = 0;
		switch($this->state)
		{
			case MIME_PARSER_START:
				$part=array(
					'Type'=>'MessageStart',
					'Position'=>$this->offset + $this->buffer_position
				);
				$this->state = MIME_PARSER_HEADER;
				break;
			case MIME_PARSER_HEADER:
				if($this->FindLineBreak($this->buffer_position, $break, $line_break))
				{
					$next = $line_break + strlen($break);
					if(!strcmp($break,"\r")
					&& strlen($this->buffer) == $next
					&& !$end)
					{
						$need_more_data = 1;
						break;
					}
					if($line_break==$this->buffer_position)
					{
						$part=array(
							'Type'=>'BodyStart',
							'Position'=>$this->offset + $this->buffer_position
						);
						$this->buffer_position = $next;
						$this->state = MIME_PARSER_BODY;
						break;
					}
				}
				if(GetType($colon=strpos($this->buffer, ':', $this->buffer_position))=='integer')
				{
					if(GetType($space=strpos(substr($this->buffer, $this->buffer_position, $colon - $this->buffer_position), ' '))=='integer')
					{
						if((!$this->mbox
						|| strcmp(strtolower(substr($this->buffer, $this->buffer_position, $space)), 'from'))
						&& !$this->SetPositionedWarning('invalid header name line', $this->buffer_position))
							return(0);
						$next = $this->buffer_position + $space + 1;
					}
					else
						$next = $colon+1;
				}
				else
				{
					$need_more_data = 1;
					break;
				}
				$part=array(
					'Type'=>'HeaderName',
					'Name'=>substr($this->buffer, $this->buffer_position, $next - $this->buffer_position),
					'Position'=>$this->offset + $this->buffer_position
				);
				$this->buffer_position = $next;
				$this->state = MIME_PARSER_HEADER_VALUE;
				break;
			case MIME_PARSER_HEADER_VALUE:
				$position = $this->buffer_position;
				$value = '';
				for(;;)
				{
					if($this->FindLineBreak($position, $break, $line_break))
					{
						$next = $line_break + strlen($break);
						$line = substr($this->buffer, $position, $line_break - $position);
						if(strlen($this->buffer) == $next)
						{
							if(!$end)
							{
								$need_more_data = 1;
								break 2;
							}
							$value .= $line;
							$part=array(
								'Type'=>'HeaderValue',
								'Value'=>$value,
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->buffer_position = $next;
							$this->state = MIME_PARSER_END;
							break ;
						}
						else
						{
							$character = $this->buffer[$next];
							if(!strcmp($character, ' ')
							|| !strcmp($character, "\t"))
							{
								$value .= $line;
								$position = $next;
							}
							else
							{
								$value .= $line;
								$part=array(
									'Type'=>'HeaderValue',
									'Value'=>$value,
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $next;
								$this->state = MIME_PARSER_HEADER;
								break 2;
							}
						}
					}
					else
					{
						if(!$end)
						{
							$need_more_data = 1;
							break;
						}
						else
						{
							$value .= substr($this->buffer, $position);
							$part=array(
								'Type'=>'HeaderValue',
								'Value'=>$value,
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->buffer_position = strlen($this->buffer);
							$this->state = MIME_PARSER_END;
							break;
						}
					}
				}
				break;
			case MIME_PARSER_BODY:
				if($this->mbox)
				{
					$add = 0;
					$append='';
					if($this->FindLineBreak($this->buffer_position, $break, $line_break))
					{
						$next = $line_break + strlen($break);
						$following = $next + strlen($break);
						if($following >= strlen($this->buffer)
						|| GetType($line=strpos($this->buffer, $break, $following))!='integer')
						{
							if(!$end)
							{
								$need_more_data = 1;
								break;
							}
						}
						$start = strtolower(substr($this->buffer, $next, strlen($break.'from ')));
						if(!strcmp($break.'from ', $start))
						{
							if($line_break == $this->buffer_position)
							{
								$part=array(
									'Type'=>'MessageEnd',
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $following;
								$this->state = MIME_PARSER_START;
								break;
							}
							else
								$add = strlen($break);
							$next = $line_break;
						}
						else
						{
							$start = strtolower(substr($this->buffer, $next, strlen('>from ')));
							if(!strcmp('>from ', $start))
							{
								$part=array(
									'Type'=>'BodyData',
									'Data'=>substr($this->buffer, $this->buffer_position, $next - $this->buffer_position),
									'Position'=>$this->offset + $this->buffer_position
								);
								$this->buffer_position = $next + 1;
								break;
							}
						}
					}
					else
					{
						if(!$end)
						{
							$need_more_data = 1;
							break;
						}
						$next = strlen($this->buffer);
						$append="\r\n";
					}
					if($next > $this->buffer_position)
					{
						$part=array(
							'Type'=>'BodyData',
							'Data'=>substr($this->buffer, $this->buffer_position, $next + $add - $this->buffer_position).$append,
							'Position'=>$this->offset + $this->buffer_position
						);
					}
					elseif($end)
					{
						$part=array(
							'Type'=>'MessageEnd',
							'Position'=>$this->offset + $this->buffer_position
						);
						$this->state = MIME_PARSER_END;
					}
					$this->buffer_position = $next;
				}
				else
				{
					if(strlen($this->buffer)-$this->buffer_position)
					{
						$data=substr($this->buffer, $this->buffer_position, strlen($this->buffer) - $this->buffer_position);
						$end_line = (!strcmp(substr($data,-1),"\n") || !strcmp(substr($data,-1),"\r"));
						if($end
						&& !$end_line)
						{
							$data.="\n";
							$end_line = 1;
						}
						$offset = $this->offset + $this->buffer_position;
						$this->buffer_position = strlen($this->buffer);
						$need_more_data = !$end;
						if(!$end_line)
						{
							if(GetType($line_break=strrpos($data, "\n"))=='integer'
							|| GetType($line_break=strrpos($data, "\r"))=='integer')
							{
								$line_break++;
								$this->buffer_position -= strlen($data) - $line_break;
								$data = substr($data, 0, $line_break);
							}
						}
						$part=array(
							'Type'=>'BodyData',
							'Data'=>$data,
							'Position'=>$offset
						);
					}
					else
					{
						if($end)
						{
							$part=array(
								'Type'=>'MessageEnd',
								'Position'=>$this->offset + $this->buffer_position
							);
							$this->state = MIME_PARSER_END;
						}
						else
							$need_more_data = 1;
					}
				}
				break;
			default:
				return($this->SetPositionedError($this->state.' is not a valid parser state', $this->buffer_position));
		}
		return(1);
	}

	Function QueueBodyParts()
	{
		for(;;)
		{
			if(!$this->body_parser->GetPart($part,$end))
				return($this->SetError($this->body_parser->error));
			if($end)
				return(1);
			if(!IsSet($part['Part']))
				$part['Part']=$this->headers['Boundary'];
			$this->parts[]=$part;
		}
	}

	Function ParseParameters($value, &$first, &$parameters, $return)
	{
		$first = strtolower(trim(strtok($value, ';')));
		$values = trim(strtok(''));
		$parameters = array();
		$return_value = '';
		while(strlen($values))
		{
			$parameter = trim(strtolower(strtok($values, '=')));
			$value = trim(strtok(';'));
			if(!strcmp($value[0], '"')
			&& !strcmp($value[strlen($value) - 1], '"'))
				$value = substr($value, 1, strlen($value) - 2);
			$parameters[$parameter] = $value;
			if(!strcmp($parameter, $return))
				$return_value = $value;
			$values = trim(strtok(''));
		}
		return($return_value);
	}

	Function DecodePart($part)
	{
		switch($part['Type'])
		{
			case 'MessageStart':
				$this->headers=array();
				break;
			case 'HeaderName':
				if($this->decode_bodies)
					$this->current_header = strtolower($part['Name']);
				break;
			case 'HeaderValue':
				if($this->decode_headers)
				{
					$value = $part['Value'];
					$error = '';
					for($decoded_header = array(), $position = 0; $position<strlen($value); )
					{
						if(GetType($encoded=strpos($value,'=?', $position))!='integer')
						{
							if($position<strlen($value))
							{
								if(count($decoded_header))
									$decoded_header[count($decoded_header)-1]['Value'].=substr($value, $position);
								else
								{
									$decoded_header[]=array(
										'Value'=>substr($value, $position),
										'Encoding'=>'ASCII'
									);
								}
							}
							break;
						}
						$set = $encoded + 2;
						if(GetType($method=strpos($value,'?', $set))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $set;
							break;
						}
						$encoding=strtoupper(substr($value, $set, $method - $set));
						$method += 1;
						if(GetType($data=strpos($value,'?', $method))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $set;
							break;
						}
						$start = $data + 1;
						if(GetType($end=strpos($value,'?=', $start))!='integer')
						{
							$error = 'invalid header encoding syntax '.$part['Value'];
							$error_position = $part['Position'] + $start;
							break;
						}
						if($encoded > $position)
						{
							if(count($decoded_header))
								$decoded_header[count($decoded_header)-1]['Value'].=substr($value, $position, $encoded - $position);
							else
							{
								$decoded_header[]=array(
									'Value'=>substr($value, $position, $encoded - $position),
									'Encoding'=>'ASCII'
								);
							}
						}
						switch(strtolower(substr($value, $method, $data - $method)))
						{
							case 'q':
								if($end>$start)
								{
									for($decoded = '', $position = $start; $position < $end ; )
									{
										switch($value[$position])
										{
											case '=':
												$h = HexDec($hex = strtolower(substr($value, $position+1, 2)));
												if($end - $position < 3
												|| strcmp(sprintf('%02x', $h), $hex))
												{
													$warning = 'the header specified an invalid encoded character';
													$warning_position = $part['Position'] + $position + 1;
													if($this->ignore_syntax_errors)
													{
														$this->SetPositionedWarning($warning, $warning_position);
														$decoded .= '=';
														$position ++;
													}
													else
													{
														$error = $warning;
														$error_position = $warning_position;
														break 4;
													}
												}
												else
												{
													$decoded .= Chr($h);
													$position += 3;
												}
												break;
											case '_':
												$decoded .= ' ';
												$position++;
												break;
											default:
												$decoded .= $value[$position];
												$position++;
												break;
										}
									}
									if(count($decoded_header)
									&& (!strcmp($decoded_header[$last = count($decoded_header)-1]['Encoding'], 'ASCII'))
									|| !strcmp($decoded_header[$last]['Encoding'], $encoding))
									{
										$decoded_header[$last]['Value'].= $decoded;
										$decoded_header[$last]['Encoding']= $encoding;
									}
									else
									{
										$decoded_header[]=array(
											'Value'=>$decoded,
											'Encoding'=>$encoding
										);
									}
								}
								break;
							case 'b':
								$decoded=base64_decode(substr($value, $start, $end - $start));
								if($end <= $start
								|| GetType($decoded) != 'string'
								|| strlen($decoded) == 0)
								{
									$warning = 'the header specified an invalid base64 encoded text';
									$warning_position = $part['Position'] + $start;
									if($this->ignore_syntax_errors)
										$this->SetPositionedWarning($warning, $warning_position);
									else
									{
										$error = $warning;
										$error_position = $warning_position;
										break 2;
									}
								}
								if(count($decoded_header)
								&& (!strcmp($decoded_header[$last = count($decoded_header)-1]['Encoding'], 'ASCII'))
								|| !strcmp($decoded_header[$last]['Encoding'], $encoding))
								{
									$decoded_header[$last]['Value'].= $decoded;
									$decoded_header[$last]['Encoding']= $encoding;
								}
								else
								{
									$decoded_header[]=array(
										'Value'=>$decoded,
										'Encoding'=>$encoding
									);
								}
								break;
							default:
								$error = 'the header specified an unsupported encoding method';
								$error_position = $part['Position'] + $method;
								break 2;
						}
						$position = $end + 2;
					}
					if(strlen($error)==0
					&& count($decoded_header))
						$part['Decoded']=$decoded_header;
				}
				if($this->decode_bodies
				|| $this->decode_headers)
				{
					switch($this->current_header)
					{
						case 'content-type:':
							$boundary = $this->ParseParameters($part['Value'], $type, $parameters, 'boundary');
							$this->headers['Type'] = $type;
							if($this->decode_headers)
							{
								$part['MainValue'] = $type;
								$part['Parameters'] = $parameters;
							}
							if(!strcmp(strtok($type, '/'), 'multipart'))
							{
								$this->headers['Multipart'] = 1;
								if(strlen($boundary))
									$this->headers['Boundary'] = $boundary;
								else
									return($this->SetPositionedError('multipart content-type header does not specify the boundary parameter', $part['Position']));
							}
							break;
						case 'content-transfer-encoding:':
							switch($this->headers['Encoding']=strtolower(trim($part['Value'])))
							{
								case 'quoted-printable':
									$this->headers['QuotedPrintable'] = 1;
									break;
								case '7 bit':
								case '8 bit':
									if(!$this->SetPositionedWarning('"'.$this->headers['Encoding'].'" is an incorrect content transfer encoding type', $part['Position']))
										return(0);
								case '7bit':
								case '8bit':
								case 'binary':
									break;
								case 'base64':
									$this->headers['Base64']=1;
									break;
								default:
									if(!$this->SetPositionedWarning('decoding '.$this->headers['Encoding'].' encoded bodies is not yet supported', $part['Position']))
										return(0);
							}
							break;
					}
				}
				break;
			case 'BodyStart':
				if($this->decode_bodies
				&& IsSet($this->headers['Multipart']))
				{
					$this->body_parser_state = MIME_PARSER_BODY_START;
					$this->body_buffer = '';
					$this->body_buffer_position = 0;
				}
				break;
			case 'MessageEnd':
				if($this->decode_bodies
				&& IsSet($this->headers['Multipart'])
				&& $this->body_parser_state != MIME_PARSER_BODY_DONE)
					return($this->SetPositionedError('incomplete message body part', $part['Position']));
				break;
			case 'BodyData':
				if($this->decode_bodies)
				{
					if(strlen($this->body_buffer)==0)
					{
						$this->body_buffer = $part['Data'];
						$this->body_offset = $part['Position'];
					}
					else
						$this->body_buffer .= $part['Data'];
					if(IsSet($this->headers['Multipart']))
					{
						$boundary = '--'.$this->headers['Boundary'];
						switch($this->body_parser_state)
						{
							case MIME_PARSER_BODY_START:
								for($position = $this->body_buffer_position; ;)
								{
									if(!$this->FindBodyLineBreak($position, $break, $line_break))
										return(1);
									$next = $line_break + strlen($break);
									if(!strcmp(substr($this->body_buffer, $position, $line_break - $position), $boundary))
									{
										$part=array(
											'Type'=>'StartPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $next
										);
										$this->parts[]=$part;
										UnSet($this->body_parser);
										$this->body_parser = new App_Mail_MimeMailParser();
										$this->body_parser->decode_bodies = 1;
										$this->body_parser->decode_headers = $this->decode_headers;
										$this->body_parser->mbox = 0;
										$this->body_parser_state = MIME_PARSER_BODY_DATA;
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_offset += $next;
										$this->body_buffer_position = 0;
										break;
									}
									else
										$position = $next;
								}
							case MIME_PARSER_BODY_DATA:
								for($position = $this->body_buffer_position; ;)
								{
									if(!$this->FindBodyLineBreak($position, $break, $line_break))
									{
										if($position > 0)
										{
											if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 0))
												return($this->SetError($this->body_parser->error));
											if(!$this->QueueBodyParts())
												return(0);
										}
										$this->body_buffer = substr($this->body_buffer, $position);
										$this->body_buffer_position = 0;
										$this->body_offset += $position;
										return(1);
									}
									$next = $line_break + strlen($break);
									$line = substr($this->body_buffer, $position, $line_break - $position);
									if(!strcmp($line, $boundary))
									{
										if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 1))
											return($this->SetError($this->body_parser->error));
										if(!$this->QueueBodyParts())
											return(0);
										$part=array(
											'Type'=>'EndPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $position
										);
										$this->parts[] = $part;
										$part=array(
											'Type'=>'StartPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $next
										);
										$this->parts[] = $part;
										UnSet($this->body_parser);
										$this->body_parser = new App_Mail_MimeMailParser();
										$this->body_parser->decode_bodies = 1;
										$this->body_parser->decode_headers = $this->decode_headers;
										$this->body_parser->mbox = 0;
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_buffer_position = 0;
										$this->body_offset += $next;
										$position=0;
										continue;
									}
									elseif(!strcmp($line, $boundary.'--'))
									{
										if(!$this->body_parser->Parse(substr($this->body_buffer, 0, $position), 1))
											return($this->SetError($this->body_parser->error));
										if(!$this->QueueBodyParts())
											return(0);
										$part=array(
											'Type'=>'EndPart',
											'Part'=>$this->headers['Boundary'],
											'Position'=>$this->body_offset + $position
										);
										$this->body_buffer = substr($this->body_buffer, $next);
										$this->body_buffer_position = 0;
										$this->body_offset += $next;
										$this->body_parser_state = MIME_PARSER_BODY_DONE;
										break 2;
									}
									$position = $next;
								}
								break;
							case MIME_PARSER_BODY_DONE:
								return(1);
							default:
								return($this->SetPositionedError($this->state.' is not a valid body parser state', $this->body_buffer_position));
						}
					}
					elseif(IsSet($this->headers['QuotedPrintable']))
					{
						for($end = strlen($this->body_buffer), $decoded = '', $position = $this->body_buffer_position; $position < $end; )
						{
							if(GetType($equal = strpos($this->body_buffer, '=', $position))!='integer')
							{
								$decoded .= substr($this->body_buffer, $position);
								$position = $end;
								break;
							}
							$next = $equal + 1;
							switch($end - $equal)
							{
								case 1:
									$decoded .= substr($this->body_buffer, $position, $equal - $position);
									$position = $equal;
									break 2;
								case 2:
									$decoded .= substr($this->body_buffer, $position, $equal - $position);
									if(!strcmp($this->body_buffer[$next],"\n"))
										$position = $end;
									else
										$position = $equal;
									break 2;
							}
							if(!strcmp(substr($this->body_buffer, $next, 2), $break="\r\n")
							|| !strcmp($this->body_buffer[$next], $break="\n")
							|| !strcmp($this->body_buffer[$next], $break="\r"))
							{
								$decoded .= substr($this->body_buffer, $position, $equal - $position);
								$position = $next + strlen($break);
								continue;
							}
							$decoded .= substr($this->body_buffer, $position, $equal - $position);
							$h = HexDec($hex=strtolower(substr($this->body_buffer, $next, 2)));
							if(strcmp(sprintf('%02x', $h), $hex))
							{
								if(!$this->SetPositionedWarning('the body specified an invalid quoted-printable encoded character', $this->body_offset + $next))
									return(0);
								$decoded.='=';
								$position=$next;
							}
							else
							{
								$decoded .= Chr($h);
								$position = $equal + 3;
							}
						}
						if(strlen($decoded)==0)
						{
							$this->body_buffer_position = $position;
							return(1);
						}
						$part['Data'] = $decoded;
						$this->body_buffer = substr($this->body_buffer, $position);
						$this->body_buffer_position = 0;
						$this->body_offset += $position;
					}
					elseif(IsSet($this->headers['Base64']))
					{
						$part['Data'] = base64_decode($this->body_buffer_position ? substr($this->body_buffer,$this->body_buffer_position) : $this->body_buffer);
						$this->body_offset += strlen($this->body_buffer) - $this->body_buffer_position;
						$this->body_buffer_position = 0;
						$this->body_buffer = '';
					}
					else
					{
						$part['Data'] = substr($this->body_buffer, $this->body_buffer_position);
						$this->body_buffer_position = 0;
						$this->body_buffer = '';
					}
				}
				break;
		}
		$this->parts[]=$part;
		return(1);
	}

	Function DecodeStream(&$end_of_message, &$decoded)
	{
		$end_of_message = 1;
		$state = MIME_MESSAGE_START;
		for(;;)
		{
			if(!$this->GetPart($part, $end))
				return(0);
			if($end)
			{
				if ($this->end_of_data) break;
				$this->end_of_data = $this->parser->getMessagePart($data);

				if(!$this->Parse($data, $end_of_data))
					return(0);
				continue;
			}
			$type = $part['Type'];
			switch($state)
			{
				case MIME_MESSAGE_START:
					switch($type)
					{
						case 'MessageStart':
							$decoded=array(
								'Headers'=>array(),
								'Parts'=>array()
							);
							$end_of_message = 0;
							$state = MIME_MESSAGE_GET_HEADER_NAME;
							continue 3;
					}
					break;

				case MIME_MESSAGE_GET_HEADER_NAME:
					switch($type)
					{
						case 'HeaderName':
							$header = strtolower($part['Name']);
							$state = MIME_MESSAGE_GET_HEADER_VALUE;
							continue 3;
						case 'BodyStart':
							$state = MIME_MESSAGE_GET_BODY;
							$part_number = 0;
							continue 3;
					}
					break;

				case MIME_MESSAGE_GET_HEADER_VALUE:
					switch($type)
					{
						case 'HeaderValue':
							$value = trim($part['Value']);
							if(!IsSet($decoded['Headers'][$header]))
							{
								$h = 0;
								$decoded['Headers'][$header]=$value;
							}
							elseif(GetType($decoded['Headers'][$header])=='string')
							{
								$h = 1;
								$decoded['Headers'][$header]=array($decoded['Headers'][$header], $value);
							}
							else
							{
								$h = count($decoded['Headers'][$header]);
								$decoded['Headers'][$header][]=$value;
							}
							if(IsSet($part['Decoded'])
							&& (count($part['Decoded'])>1
							|| strcmp($part['Decoded'][0]['Encoding'],'ASCII')
							|| strcmp($value, trim($part['Decoded'][0]['Value']))))
							{
								$p=$part['Decoded'];
								$p[0]['Value']=ltrim($p[0]['Value']);
								$last=count($p)-1;
								$p[$last]['Value']=rtrim($p[$last]['Value']);
								$decoded['DecodedHeaders'][$header][$h]=$p;
							}
							switch($header)
							{
								case 'content-disposition:':
									$filename='filename';
									break;
								case 'content-type:':
									if(!IsSet($decoded['FileName']))
									{
										$filename='name';
										break;
									}
								default:
									$filename='';
									break;
							}
							if(strlen($filename))
							{
								$this->ParseStructuredHeader($value, $type, $header_parameters, $character_sets, $languages);
								if(IsSet($header_parameters[$filename]))
								{
									$decoded['FileName']=$header_parameters[$filename];
									if(IsSet($character_sets[$filename])
									&& strlen($character_sets[$filename]))
										$decoded['FileNameCharacterSet']=$character_sets[$filename];
									if(IsSet($character_sets['language'])
									&& strlen($character_sets['language']))
										$decoded['FileNameCharacterSet']=$character_sets[$filename];
									if(!strcmp($header, 'content-disposition:'))
										$decoded['FileDisposition']=$type;
								}
							}
							$state = MIME_MESSAGE_GET_HEADER_NAME;
							continue 3;
					}
					break;

				case MIME_MESSAGE_GET_BODY:
					switch($type)
					{
						case 'BodyData':
							
								if(!IsSet($decoded['BodyFile']))
								{
									$directory_separator= '/';
								    $path = TEMP_PATH . preg_replace('/[^a-zA-Z0-9]/', '_', $this->unique_message_id.'.'. strval($this->body_part_number));
									if(!($this->body_file = fopen($path, 'wb')))
										return($this->SetPHPError('could not create file '.$path.' to save the message body part', $php_errormsg));
									$decoded['BodyFile'] = $path;
									$decoded['BodyPart'] = $this->body_part_number;
									$decoded['BodyLength'] = 0;
									$this->body_part_number++;
								}
								if(strlen($part['Data'])
								&& !fwrite($this->body_file, $part['Data']))
								{
									$this->SetPHPError('could not save the message body part to file '.$decoded['BodyFile'], $php_errormsg);
									fclose($this->body_file);
									$this->body_file = null;
									@unlink($decoded['BodyFile']);
									return(0);
								}
							
							$decoded['BodyLength'] += strlen($part['Data']);
							continue 3;
						case 'StartPart':
							if(!$this->DecodeStream($end_of_part, $decoded_part))
								return(0);
							$decoded['Parts'][$part_number]=$decoded_part;
							$part_number++;
							$state = MIME_MESSAGE_GET_BODY_PART;
							continue 3;
						case 'MessageEnd':
							if(IsSet($decoded['BodyFile'])) {
								fclose($this->body_file);
								$this->body_file = null;
							}
							return(1);
					}
					break;

				case MIME_MESSAGE_GET_BODY_PART:
					switch($type)
					{
						case 'EndPart':
							$state = MIME_MESSAGE_GET_BODY;
							continue 3;
					}
					break;
			}
			return($this->SetError('unexpected decoded message part type '.$type.' in state '.$state));
		}
		return(1);
	}

	/* Public functions */

	Function Parse($data, $end)
	{
		if(strlen($this->error))
			return(0);
		if($this->state==MIME_PARSER_END)
			return($this->SetError('the parser already reached the end'));
		$this->buffer .= $data;
		do
		{
			Unset($part);
			if(!$this->ParsePart($end, $part, $need_more_data)) {
				return(0);
			}
			if(IsSet($part)	&& !$this->DecodePart($part)) {
				return(0);
			}
		}
		while(!$need_more_data
		&& $this->state!=MIME_PARSER_END);
		if($end
		&& $this->state!=MIME_PARSER_END)
			return($this->SetError('reached a premature end of data'));
		if($this->buffer_position>0)
		{
			$this->offset += $this->buffer_position;
			$this->buffer = substr($this->buffer, $this->buffer_position);
			$this->buffer_position = 0;
		}
		return(1);
	}

	Function GetPart(&$part, &$end)
	{
		$end = ($this->part_position >= count($this->parts));
		if($end)
		{
			if($this->part_position)
			{
				$this->part_position = 0;
				$this->parts = array();
			}
		}
		else
		{
			$part = $this->parts[$this->part_position];
			$this->part_position ++;
		}
		return(1);
	}

	Function Decode(&$decoded)
	{
		$this->warnings = $decoded = array();
	    $this->ResetParserState();
		for($message = 0; ($success = $this->DecodeStream($end_of_message, $decoded_message)) && !$end_of_message; $message++) {
            if ($this->body_file) {
                fclose($this->body_file);
                $this->body_file = null;		    
            }
			$decoded[$message]=$decoded_message;
		}
		return($success);
	}
	
	
}

?>