<?php
// Multicontact extension, https://github.com/GiovanniSalmeri/yellow-multicontact

class YellowMulticontact {
    const VERSION = "0.8.18";
    public $yellow;         //access to API
    private $smtpSocket;

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("multicontactAjax", "1");
        $this->yellow->language->setDefaults([
            "Language: en",
            "MulticontactName: Name",
            "MulticontactEmail: Email",
            "MulticontactSubject: Subject",
            "MulticontactDefaultSubject: Contact Form",
            "MulticontactMessage: Message",
            "MulticontactButton: Send message",
            "MulticontactMessageSent: You have sent a message. Thank you!",
            "MulticontactMessageNotSent: Message could not be sent",
            "Language: it",
            "MulticontactName: Nome",
            "MulticontactEmail: Email",
            "MulticontactSubject: Oggetto",
            "MulticontactDefaultSubject: Modulo di contatto",
            "MulticontactMessage: Messaggio",
            "MulticontactButton: Invia il messaggio",
            "MulticontactMessageSent: Il tuo messaggio è stato inviato con successo. Grazie!",
            "MulticontactMessageNotSent: C'è stato un problema nell'invio",
            "Language: sv",
            "MulticontactName: Namn",
            "MulticontactEmail: Email",
            "MulticontactSubject: Ämne",
            "MulticontactDefaultSubject: Kontaktformulär",
            "MulticontactMessage: Meddelande",
            "MulticontactButton: Skicka meddelande",
            "MulticontactMessageSent: Ditt meddelande har nu skickats. Tack!",
            "MulticontactMessageNotSent: Meddelandet kunde inte skickas",
            "Language: fr",
            "MulticontactName: Nom",
            "MulticontactEmail: Email",
            "MulticontactSubject: Object",
            "MulticontactDefaultSubject: Formulaire de contact",
            "MulticontactMessage: Message",
            "MulticontactButton: Envoyer le message",
            "MulticontactMessageSent: Votre message a bien été envoyé. Merci !",
            "MulticontactMessageNotSent: Votre message n'a pas pu être envoyé",
            "Language: de",
            "MulticontactName: Name",
            "MulticontactEmail: Email",
            "MulticontactSubject: Betreff",
            "MulticontactDefaultSubject: Kontakt-Formular",
            "MulticontactMessage: Nachricht",
            "MulticontactButton: Nachricht absenden",
            "MulticontactMessageSent: Nachricht wurde versandt. Vielen Dank!",
            "MulticontactMessageNotSent: Nachricht konnte nicht versandt werden",
            "Language: es",
            "MulticontactName: Nombre",
            "MulticontactEmail: Email",
            "MulticontactSubject: Asunto",
            "MulticontactDefaultSubject: Formulario de contacto",
            "MulticontactMessage: Mensaje",
            "MulticontactButton: Enviar mensaje",
            "MulticontactMessageSent: Enviaste un mensaje. ¡Gracias!",
            "MulticontactMessageNotSent: El mensaje no pudo ser enviado",
            "Language: pt",
            "MulticontactName: Nome",
            "MulticontactEmail: Email",
            "MulticontactSubject: Assunto",
            "MulticontactDefaultSubject: Formulário de contato",
            "MulticontactMessage: Mensagem",
            "MulticontactButton: Enviar mensagem",
            "MulticontactMessageSent: O seu email foi enviado com sucesso. Obrigado!",
            "MulticontactMessageNotSent: O seu email não pôde ser enviado",
            "Language: pl",
            "MulticontactName: Imię i nazwisko",
            "MulticontactEmail: Adres email",
            "MulticontactSubject: Temat",
            "MulticontactDefaultSubject: Wiadomość z formularza witryny",
            "MulticontactMessage: Wiadomość",
            "MulticontactButton: Wyślij wiadomość",
            "MulticontactMessageSent: Wiadomość została wysłana.",
            "MulticontactMessageNotSent: Wiadomość nie mogła zostać wysłana",
        ]);
    }

    // Handle page content parsing of custom block
    public function onParseContentShortcut($page, $name, $text, $type) {
        $output = null;
        $statusMessage = null;
        if ($name=="multicontact" && ($type=="block" || $type=="inline")) {
            if ($this->yellow->extension->isExisting("mailer")) {
                $subjects = $this->yellow->toolbox->getTextArguments($text);
                $addresses = [];
                foreach ($subjects as $subject) {
                    if (@preg_match('/^(.*)\s+(\S+)$/', $subject, $matches)) {
                        $addresses[$matches[1]] = $matches[2];
                    }
                }

                if ($page->isRequest("send")) {
                    if (count($addresses)==0) {
                        $toEmail = $this->yellow->page->isExisting("email") ? $this->yellow->page->get("email") : $this->yellow->system->get("email");
                        $subject = [ $this->yellow->language->getTextHtml("multicontactDefaultSubject") => $toEmail ];
                    } elseif (count($addresses)==1) {
                        $subject = $addresses;
                    } else {
                        $subject = array_slice($addresses, $page->getRequest("subject"), 1);
                    }

                    $headers = [];
                    $headers["to"] = [ reset($subject) ];
                    $headers["from"] = [ $page->getRequest("name") => $page->getRequest("email") ];
                    $headers["subject"] = "[".$this->yellow->system->get("sitename")."] ".key($subject);
                    $message = [];
                    $message["text"]["plain"]["body"] = $page->getRequest("message");
                    $message["text"]["plain"]["signature"] = $page->getRequest("name");

                    $status = $this->yellow->toolbox->mail("multicontact", $headers, $message);
                    $statusMessage = $status ? $this->yellow->language->getTextHtml("multicontactMessageSent") : $this->yellow->language->getTextHtml("multicontactMessageNotSent");
                    if ($page->getRequest("__httprequest")=="xmlhttp") {
                        @header("Content-Type: application/json; charset=utf-8");
                        echo json_encode([ $status, $statusMessage ]);
                        exit();
                    }

                }
                if (!$page->isRequest("send") || $result[0]===false) {
                    $extensionLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreExtensionLocation");
                    $output .= "<form method=\"post\" id=\"multicontact-form\">\n";
                    $output .= "<div><label>".$this->yellow->language->getTextHtml("multicontactName")."<br /><input class=\"form-control\" type=\"text\" size=\"40\" required=\"required\" name=\"name\" id=\"name\" value=\"".$page->getRequestHtml("name")."\" /></label></div>\n";
                    $output .= "<div><label>".$this->yellow->language->getTextHtml("multicontactEmail")."<br /><input class=\"form-control\" type=\"email\" size=\"40\" required=\"required\" name=\"email\" id=\"email\" value=\"".$page->getRequestHtml("email")."\" /></label></div>\n";
                    if (count($addresses)>1) {
                        $output .= "<div><label>".$this->yellow->language->getTextHtml("multicontactSubject")."<br />\n";
                        $output .= "<select name=\"subject\" id=\"subject\">\n";
                        foreach (array_keys($addresses) as $count=>$subjectName) {
                            $output .= "<option value=\"".$count."\"".($page->getRequest("subject")==$count ? " selected=\"selected\"" : "").">".$subjectName."</option>\n";
                        }
                        $output .= "</select></label></div>\n";
                    }
                    $output .= "<div><label>".$this->yellow->language->getTextHtml("multicontactMessage")."<br /><textarea class=\"form-control\" required=\"required\" name=\"message\" id=\"message\" rows=\"10\" cols=\"60\">".$page->getRequestHtml("message")."</textarea></label></div>\n";
                    $output .= "<div><input type=\"hidden\" name=\"send\" id=\"send\" value=\"send\" /></div>\n";
                    $output .= "<div><input class=\"btn\" type=\"submit\" value=\"".$this->yellow->language->getTextHtml("multicontactButton")."\">";
                    if ($this->yellow->system->get("multicontactAjax")) $output .= "<img id=\"multicontact-spinner\" src=\"{$extensionLocation}multicontact-spinner.svg\" aria-hidden=\"true\" alt=\"\" />";
                    $output .= "</div>\n</form>\n";
                }
                $output .= "<div id=\"multicontact-message\"".($statusMessage ? " role=\"alert\"" : "").">".$statusMessage."</div>\n";
            } else {
                $page->error(500, "Multicontact requires 'mailer' extension!");
            }
        }
        return $output;
    }

    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        if ($name=="header") {
            $extensionLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreExtensionLocation");
            $output .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$extensionLocation}multicontact.css\" />\n";
            if ($this->yellow->system->get("multicontactAjax")) $output .= "<script type=\"text/javascript\" defer=\"defer\" src=\"{$extensionLocation}multicontact.js\"></script>\n";
        }
        return $output;
    }

}
