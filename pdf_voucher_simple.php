<?php
require __DIR__.'/vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Clase para generar Comprobantes PDF de recibos no fiscales
 *
 * @author NeoComplexx Group S.A.
 */
class PDFVoucherSimple extends HTML2PDF {

    private $config = array();
    private $voucher = null;
    private $finished = false; //Determina si es la ultima pagina
    private $html = "";
    private $lang = array();
    const LANG_EN = 2;

    function __construct($voucher, $config) {
        parent::__construct('L', 'A5', 'es');
        $this->config = $config;
        $vconfig = array();
        $this->config["VOUCHER_CONFIG"] = $vconfig;
        $this->voucher = $voucher;
        $this->finished = false;
        $cssfile = fopen(dirname(__FILE__) . "/voucher.css", "r");
        $css = fread($cssfile, filesize(dirname(__FILE__) . "/voucher.css"));
        fclose($cssfile);
        $this->html = "<style>" . $css . "</style>";

        if (array_key_exists("idiomaComprobante",$voucher) && $voucher["idiomaComprobante"] == $this::LANG_EN) {
            include(__DIR__.'/language/en.php');
        } else {
            include(__DIR__.'/language/es.php');
        }
        $this->lang = array_merge($this->lang, $lang);
    }

    private function lang($key) {
        if (array_key_exists($key,$this->lang)) {
            return $this->lang[$key];
        } else {
            return $key;
        }
    }

    /**
     * Genera la cabecera del comprobante
     * @param String $logo_path - Ubicación de la imágen del logo
     * @param String $title - Ej: ORIGINAL/DUPLICADO
     *
     * @author NeoComplexx Group S.A.
     */
    function addVoucherInformation($logo_path, $title) {
        if ($this->show_element("header")) {
            $this->html .= "<div class='border-div'>";
            $letter = $this->voucher["letra"];
            $number = str_pad($this->voucher["numeroPuntoVenta"], 4, "0", STR_PAD_LEFT) . "-" . str_pad($this->voucher["numeroComprobante"], 8, "0", STR_PAD_LEFT);
            $type = $this->lang($this->voucher["TipoComprobante"]) . " " . $letter . " " . $number;
            
            $tmp = DateTime::createFromFormat('Ymd',$this->voucher["fechaComprobante"]);
            $date = $this->lang("Fecha de emisi&oacute;n") . ": " . date_format($tmp, $this->lang('d/m/Y'));

            $this->html .= "<table class='responsive-table table-header'>";
            $this->html .= "<tr><td style='width: 3%;'></td>";
            $this->html .= "<td style='width: 27%;'>";
            if (file_exists($logo_path)) {
                $this->html .= "<img class='logo' src='" . $logo_path . "' alt='logo'>";
            }
            $this->html .= "</td>";
            $this->html .= "<td class='right-text' style='width: 69%;'>";
            $this->html .= "    <span class='type_voucher header_margin'>$type</span><br>";
            $this->html .= "</td>";
            $this->html .= "</tr>";
            $this->html .= "</table>";
            $this->html .= "<table class='responsive-table table-header'>";
            $this->html .= "<tr>";
            $this->html .= "<td style='width:50%;'>" . $this->lang("Raz&oacute;n social") . ": " . strtoupper($this->config["TRADE_SOCIAL_REASON"]) . "</td>";
            $this->html .= "<td class='right-text' style='width:49%;'>$date</td>";
            $this->html .= "</tr>";
            $this->html .= "<tr>";
            $this->html .= "<td style='width:50%;'>" . $this->lang("Domicilio comercial") . ": " . strtoupper($this->config["TRADE_ADDRESS"]) . "</td>";
            $this->html .= "<td class='right-text' style='width:49%;'></td>";
            $this->html .= "</tr>";
            $this->html .= "</table>";
            $this->html .= "</div>";
        }
    }

    /**
     * Genera la información del receptor (cliente)
     *
     * @author: NeoComplexx Group S.A.
     */
    function addReceiverInformation() {
        if ($this->show_element("receiver")) {
            $this->html .= "<div class='border-div'>";
            $this->html .= "<table class='responsive-table table-header'>";
            $this->html .= "<tr>";
            $text = $this->lang($this->voucher["TipoDocumento"]) . ": " . $this->voucher["numeroDocumento"];
            $this->html .= "<td style='width:50%;'>" . $text . "</td>";
            $text = $this->lang("Apellido y Nombre / Raz&oacute;n Social") . ": " . strtoupper($this->voucher["nombreCliente"]);
            $this->html .= "<td class='right-text' style='width:49%;'>" . $text . "</td>";
            $this->html .= "</tr>";
            $this->html .= "</table>";
            $this->html .= "</div>";
        }
    }

    /**
     * Genera la tabla con los articulos del comprobante
     *
     * @author NeoComplexx Group S.A.
     */
    function fill() {
        $this->html .= "<table class='responsive-table table-article'>";
        $this->html .= "<tr>";
        $this->html .= "<th style='width=80%;'>" . $this->lang("Concepto") . "</th>";
        $this->html .= "<th class='right-text' style='width=20%;'>" . $this->lang("Subtotal") . "</th>";
        $this->html .= "</tr>";
        foreach ($this->voucher["items"] as $item) {
            $this->html .= "<tr>";
            $this->html .= "<td style='width=80%;'>" . $item["descripcion"] . "</td>";
            $this->html .= "<td class='right-text' style='width=20%;'>" . number_format($item["importeItem"], 2) . "</td>";
            $this->html .= "</tr>";
        }
        $this->html .= "</table>";
        $this->finished = true;
    }

    /**
     * Imprime la linea de totales
     *
     * @author NeoComplexx Group S.A.
     */
    function total_line() {
        if ($this->show_element("total_line")) {
            $this->html .= "<div class='border-div'>";
            $this->html .= '    <table class="responsive-table">';
            $this->html .= '        <tr>';
            $this->html .= '		<td class="right-text" style="width: 75%;">' . $this->lang("Subtotal") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
            $text = number_format((float) round($this->voucher["importeTotal"], 2), 2, '.', '');
            $this->html .= '		<td class="right-text" style="width: 25%;">' . $text . '</td>';
            $this->html .= '        </tr>';
            $this->html .= '        <tr>';
            $this->html .= '		<td class="right-text" style="width: 75%;">' . $this->lang("Importe otros tributos") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
            $text = number_format((float) round($this->voucher["importeOtrosTributos"], 2), 2, '.', '');
            $this->html .= '		<td class="right-text" style="width: 25%;">' . $text . '</td>';
            $this->html .= '        </tr>';
            $this->html .= '        <tr>';
            $this->html .= '		<td class="right-text" style="width: 75%;">' . $this->lang("Importe total") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
            $text = number_format((float) round($this->voucher["importeTotal"], 2), 2, '.', '');
            $this->html .= '		<td class="right-text" style="width: 25%;">' . $text . '</td>';
            $this->html .= '        </tr>';
            $this->html .= '    </table>';
            $this->html .= "</div>";
        }
    }

    /**
     * Retorna la tabla de otros tributos
     *
     * @return string
     *
     * @author NeoComplexx Group S.A.
     */
    private function othertaxes() {
        $str = "";
        if (count($this->voucher['Tributos']) > 0) {
            $str .= '    <table class="responsive-table table-article">';

            //Title
            $str .= '        <tr>';
            $str .= '            <th class="center-text" colspan=2 style="width=240px;">Otros tributos</th>';
            $str .= '        </tr>';
            $str .= '        <tr>';
            $str .= '            <th class="center-text" style="width=200px;">' . $this->lang("Descripci&oacute;n") . '</th>';
            $str .= '            <th class="center-text" style="width=40px;">' . $this->lang("Importe") . '</th>';
            $str .= '        </tr>';

            foreach ($this->voucher['Tributos'] as $tax) {
                $str .= '        <tr>';
                $str .= '            <td class="left-text" style="width=200px;">' . $tax["Desc"] . '</td>';
                $str .= '            <td class="right-text" style="width=40px;">' . $tax["Importe"] . '</td>';
                $str .= '        </tr>';
            }

            //Footer
            $str .= '        <tr>';
            $str .= '            <td class="right-text" style="width=200px;">' . $this->lang("Importe otros tributos") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
            $total = number_format((float) round($this->voucher["importeOtrosTributos"], 2), 2, '.', '');
            $str .= '            <td class="right-text" style="width=40px;">' . $total . '</td>';
            $str .= '        </tr>';
            $str .= '    </table>';
        }
        return $str;
    }

    function extra_line() {
        $extra = $this->config["VOUCHER_OBSERVATION"];
        if ($extra != "") {
            $this->html .= "<div class='border-div'>";
            $this->html .= '    <table class="responsive-table">';
            $this->html .= "        <tr><td class='center-text'style='width: 100%;'>$extra</td></tr>";
            $this->html .= '    </table>';
            $this->html .= "</div>";
        }
    }

    /**
     * Imprime el pie de pagina
     *
     * @author NeoComplexx Group S.A.
     */
    function footer() {
        if ($this->show_element("footer")) {
            $this->html .= '<page_footer>';
            $this->total_line();
            $this->extra_line();
            $this->html .= '</page_footer>';
        }
    }

    /**
     * Determina si mostrar o no una parte del comprobante
     * @param element TAG del elemento a controlar
     * 
     * @author NeoComplexx Group S.A.
     */
    private function show_element($element) {
        if (array_key_exists("VOUCHER_CONFIG", $this->config) && array_key_exists($element, $this->config["VOUCHER_CONFIG"])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Genera un comprobante de AFIP con su correspondiente original/duplicado
     *
     * @param type $logo_path Ubicación de la imágen del logo
     * 
     * @author NeoComplexx Group S.A.
     */
    function emitirPDF($logo_path) {
        //ORIGINAL
        $this->html .= "<page>";
        $this->addVoucherInformation($logo_path, $this->lang("ORIGINAL"));
        $this->addReceiverInformation();
        $this->fill();
        $this->footer();
        $this->html .= "</page>";
        $this->WriteHTML($this->html);
    }

}