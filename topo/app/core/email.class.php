<?php

require_once(lib_path.'PHPMailer_5.2.1/class.phpmailer.php');

class Email{

	/**
	 * sendMsg
	 *
	 * Envia um e-mail utilizando a bibliteca PHPMailer.
	 *
	 * @version 1.0
	 * @param 	string 	$to 					E-mail do destinat�rio.
	 * @param 	string 	$subject 				Assunto do e-mail.
	 * @param 	string 	$msg 					Mensagem do e-mail (html).
	 * @param 	string 	$configs 				Array com as configura��es do envio.
	 *											�ndice 'SMTPAuth' - Informa se a conex�o com o SMTP ser� autenticada.
	 *											�ndice 'SMTPSecure' - Define o padr�o de seguran�a (SSL, TLS, STARTTLS).
	 *											�ndice 'Host' - Host smtp para envio do e-mail.
	 *											�ndice 'SMTPAuth' - Informa se a conex�o com o SMTP ser� autenticada.
	 *											�ndice 'Port' - Porta do SMTP para envio do e-mail.
	 *											�ndice 'Username' - Usu�rio para autentica��o do SMTP.
	 *											�ndice 'Password' - Senha para autentica��o do SMTP.
	 *											�ndice 'AddReplyTo' - E-mail e nome para resposta do e-mail.
	 *													array( 0 => e-mail, 1 => nome)
	 *											�ndice 'SetFrom' - E-mail e nome do remetente do e-mail.
	 *													array( 0 => e-mail, 1 => nome)
	 * @param 	string 	$msg 					Mensagem do e-mail (html).
	 * @param 	string 	$msg 					Mensagem do e-mail (html).
	 * @param 	string 	$msg 					Mensagem do e-mail (html).
	 * @return 	boolean / mensagens de erros 	Retorna true caso o e-mail tenha sido enviado ou as mensagens de erro caso n�o tenha sido enviado.
	 */
	public static function sendMsg($to,$subject,$msg,$configs = '',$debug = 1){
	
		$configs = ($configs == '' || $configs == null)?Config::$email['default']: $configs;
		$configs = (is_string($configs))?Config::$email[$configs]: $configs;
		
		$mail = new PHPMailer(true); // True para a fun��o enviar exce��es de erros

		$mail->IsSMTP(); // Definindo a classe para utilizar SMTP.
		
		try {
		  
		  $mail->SMTPDebug  = $debug;                // Ativar informa��es de debug
		  $mail->SMTPAuth   = $configs['SMTPAuth'];  // Informa se a conex�o com o SMTP ser� autenticada.
		  $mail->SMTPSecure = $configs['SMTPSecure'];// Define o padr�o de seguran�a (SSL, TLS, STARTTLS).
		  $mail->Host       = $configs['Host'];		 // Host smtp para envio do e-mail.
		  $mail->Port       = $configs['Port'];;     // Porta do SMTP para envio do e-mail.
		  $mail->Username   = $configs['Username'];  // Usu�rio para autentica��o do SMTP.
		  $mail->Password   = $configs['Password'];  // Senha para autentica��o do SMTP.
		  $mail->AddAddress($to, "");
		  $mail->AddReplyTo($configs['AddReplyTo'][0], $configs['AddReplyTo'][1]);
		  $mail->SetFrom($configs['SetFrom'][0], $configs['SetFrom'][1]);
		  $mail->Subject = $subject;
		  $mail->AltBody = 'Para ver essa mensagem utilize um programa compat�vel com e-mails em formato HTML!'; // optional - MsgHTML will create an alternate automatically
		  $mail->MsgHTML($msg);
		  $mail->Send();
		  return true;
		} catch (phpmailerException $e) {
		  return $e->errorMessage(); // Mensagens de erro das exce��es geradas pelo phpmailer.
		} catch (Exception $e) {
		  return $e->getMessage(); // Mensagens de exce��es geradas durante a execu��o da fun��o.
		}
	}
}

?>