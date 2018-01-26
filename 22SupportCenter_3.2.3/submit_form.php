<?
/*In this file we will try to show you how you can integrate SupportCenter
into your existing web site

form requirements:
submit method: POST

accepted fields: name, email, subject, body, queue_id

required hidden fields: success_url, failed_url

Explanation of hidden fields:
success_url - to this url will be user redirected in case ticket will be submitted correctly
			- variable "message" will contain message about successfully created ticket
failed_url 	- to this url will be user redirected in case any error raised during ticket submition
			- variable "error" will contain reason, why request failed

- both hidden variables can contain value "referer" or be empty - in this case will be user redirected to same page 
				from which SupportCenter received request
*/

//please fill in following parameters acording to your environment
$protocol = 'http://';
$host = 'www.yourdomain.com';
$service_dir = '/support/server/'; //ending slash is mandatory !!!
ini_set('zend.ze1_compatibility_mode', true);



?><!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>SupportCenter - Integration example</title>
</head>
<body>

<h1>SupportCenter - Integration example</h1>

<p>
	This short example will show you how easy is <br/>
	to integrate subnmit ticket form into your existing web site.
</p>

<?

//*************************** START: HOW WE HANDLE RESPONSE ********************************

//handle error messages in case of failed request
	if (strlen($_REQUEST['error'])) {
		echo '<span style="font-weight: bolder; color: red;">
				Failed to submit your request: ' . $_REQUEST['error'] . 
			'</span><br/>';
	}

//handle correctly submitted ticket message
	if (strlen($_REQUEST['message'])) {
		echo '<span style="font-weight: bolder; color: blue;">
				' . $_REQUEST['message'] . 
			'</span><br/>';
	}

//*************************** END: HOW WE HANDLE RESPONSE **********************************
?>

<?
//*************************** START: EXAMPLE FORM ******************************************


?>
<form action="<? echo $protocol . $host . $service_dir; ?>submit_ticket.php" name="SubmitNewTicket" method="post" enctype="multipart/form-data">
<input type="hidden" name="success_url" value="referer">
<input type="hidden" name="failed_url" value="referer">
	<fieldset style="width: 450px">
	<legend>Submit new ticket form example</legend>
		<table>
			<tr>
				<td>
					Name:
				</td><td>
					<input type="text" name="name" style="width: 400px">
				</td>
			</tr>
			<tr>
				<td>
					Email:
				</td><td>
					<input type="text" name="email" style="width: 400px">
				</td>
			</tr>
			<tr>
				<td>
					Queue:
				</td><td>
					<select name="queue_id" style="width: 400px">
						<option value="" selected="selected"></option>
						<?
						//Request queue options
						//if your php is running in safe mode you can have problems to load this value !!!

                        function unicode2utf8($str)
                        {
                            $str = preg_replace("/\\\\u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
                            return html_entity_decode($str,null,'UTF-8');
                        }						
						$request = 'd={"params":[{"order":"is_default DESC,name"}],"method":"getQueueList","id":1}'; 
						# compose HTTP request header
						$header = "Host: $host\r\n";
						$header .= "User-Agent: PHP Script\r\n";
						$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
						$header .= "Content-Length: ".strlen($request)."\r\n";
						$header .= "Connection: close\r\n\r\n";
						
						if ($fp = fsockopen($host, 80, $errno, $errstr, 30)) {
						    fputs($fp, "POST " . $service_dir . "index.php?service=Queues  HTTP/1.1\r\n");
						    fputs($fp, $header.$request);
						    fwrite($fp, $out);
						    //skip headers until empty line
						    while (!feof($fp) && strlen(trim(fgets($fp, 128)))) {
						    }
						    $resp = '';
						    while (!feof($fp)) {
						        $resp .= fgets($fp);
						    }
						    fclose($fp);
						    
						    $resp = trim($resp);
						}						
						//parse server response without JSON parser
						if (preg_match('/"rs":{"rows":\[(.*?)\]}/', $resp, $match)) {
							if (preg_match_all('/\[(.*?)\]/', $match[1], $match)) {
								
								foreach($match[1] as $queue_row) {
									$line = explode(',', $queue_row);
									echo '<option value ="' . unicode2utf8(trim($line[0], '"')) . '">' . unicode2utf8(trim($line[1], '"')) . '</option>';
								}
							}
						}
						
						
						?>
					</select>					
				</td>
			</tr>
			<tr>
				<td>
					Subject:
				</td><td>
					<input type="text" name="subject" style="width: 400px">
				</td>
			</tr>
			<tr>
				<td valign="top">
					Description:
				</td>
				<td>
					<textarea name="body" rows="15" style="width: 400px"></textarea>
				</td>
			</tr>
			<TR>
				<TD style="vertical-align: top; white-space: nowrap;">Attachment:
				</TD>
				<TD style="width: 100%;">
					<input type="file" name="attachment" size="50">
				</TD>
			</TR>
		</table>
		<input type="submit" name="Submit" value="Submit">
	</fieldset>
</form>
<?
//*************************** END: EXAMPLE FORM ********************************************
?>


</body>
</html>
