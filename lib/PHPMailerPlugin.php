<?php

namespace lib;

/**
 * Classe plugin para carregar o PHPMailer
 */
class PHPMailerPlugin implements IPlugin {

    /**
     * @var PHPMailer
     */
    public $mailer;

    public function __construct() {
    }

    /**
     * Método para carregar o objeto PHPMailer
     * @return PHPMailer
     */
    public function carregar() {
        $dados = \controlador\Controlador::getDadosIni();

        require(LIB . DS . "PHPMailer/class.phpmailer.php");

        $this->mailer = new \PHPMailer();
        if ($dados['email']['smtp']) {
            $this->mailer->IsSMTP();
            $this->mailer->Host = $dados['email']['host']; 
            $this->mailer->SMTPAuth = $dados['email']['autenticar'];
            $this->mailer->Username = $dados['email']['usuario'];
            $this->mailer->Password = $dados['email']['senha'];
        }

        $this->mailer->From = $dados['email']['from_email'];
        $this->mailer->FromName = $dados['email']['from_nome'];
        $this->mailer->IsHTML($dados['email']['html']);
        $this->mailer->WordWrap = $dados['email']['wordwrap'];
        $this->mailer->AddReplyTo($this->mailer->From);
        
        $this->mailer->CharSet = $dados['l10n']['charset'];

        return $this->mailer;

    }

    public function __destruct() {
        unset($this->mailer);
    }

}
?>