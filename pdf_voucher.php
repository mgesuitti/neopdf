<?php
require __DIR__.'/vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Clase para generar Comprobantes PDF similares a los de AFIP
 *
 * @author NeoComplexx Group S.A.
 */
class PDFVoucher extends HTML2PDF {

    private $config = array();
    private $voucher = null;
    private $finished = false; //Determina si es la ultima pagina
    private $html = "";
    private $lang = array();
    const LANG_EN = 2;

    function __construct($voucher, $config) {
        parent::__construct('P', 'A4', 'es');
        $this->config = $config;
        $vconfig = array();
        //$vconfig["footer"] = false;
        //$vconfig["total_line"] = false;
        //$vconfig["header"] = false;
        //$vconfig["receiver"] = false;
        //$vconfig["footer"] = false;
        $this->config["VOUCHER_CONFIG"] = $vconfig;
        $this->voucher = $voucher;
        $this->finished = false;
        $cssfile = fopen(dirname(__FILE__) . "/voucher.css", "r");
        $css = fread($cssfile, filesize(dirname(__FILE__) . "/voucher.css"));
        fclose($cssfile);
        $this->html = "<style>" . $css . "</style>";

        if (array_key_exists("idiomaComprobante",$voucher) && $voucher["idiomaComprobante"] == $this::LANG_EN) {
            include(__DIR__.'/language/en.php');
            $this->lang = array_merge($this->lang, $lang);
        }
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
            if ($title != "") {
                $this->html .= "<div class='border-div'>";
                $this->html .= "    <h3 class='center-text'>" . $title . "</h3>";
                $this->html .= "</div>";
                $this->html .= "<div class='border-div'>";
            }

            $type = $this->lang($this->voucher["TipoComprobante"]);
            $letter = $this->voucher["letra"];
            $number = $this->lang("Punto de venta") . ": " . str_pad($this->voucher["numeroPuntoVenta"], 4, "0", STR_PAD_LEFT) . "   " . $this->lang("Comp. Nro") . ": " . str_pad($this->voucher["numeroComprobante"], 8, "0", STR_PAD_LEFT);
            $tmp = DateTime::createFromFormat('Ymd',$this->voucher["fechaComprobante"]);
            $date = $this->lang("Fecha de emisi&oacute;n") . ": " . date_format($tmp, $this->lang('d/m/Y'));

            $this->html .= "    <div class='letter'>";
            $this->html .= "        <p class='title'>$letter</p> ";
            $id_type = $this->voucher["codigoTipoComprobante"];
            $this->html .= "        <p class='type'>Cod. $id_type</p> ";
            $this->html .= "    </div>";

            $this->html .= "<table class='responsive-table table-header'>";
            $this->html .= "<tr><td style='width: 3%;'></td>";
            $this->html .= "<td style='width: 27%;'>";
            if (file_exists($logo_path)) {
                $this->html .= "<img class='logo' src='" . $logo_path . "' alt='logo'>";
            }
            $this->html .= "</td>";
            $this->html .= "<td class='right-text' style='width: 69%;'>";
            $this->html .= "    <span class='type_voucher header_margin'>$type</span><br>";
            $this->html .= "    <span class='header_margin'>$number</span><br>";
            $this->html .= "    <span class='header_margin'>$date</span>";
            if ($this->voucher["codigoConcepto"] == 2 || $this->voucher["codigoConcepto"] == 3) {
                $tmp = DateTime::createFromFormat('Ymd',$this->voucher["fechaDesde"]);
                $service_from = $this->lang("Per&iacute;odo") . ": " . date_format($tmp, $this->lang('d/m/Y'));
                $tmp = DateTime::createFromFormat('Ymd',$this->voucher["fechaHasta"]);
                $service_to = $this->lang("al") . " " . date_format($tmp, $this->lang('d/m/Y'));
                $tmp = DateTime::createFromFormat('Ymd',$this->voucher["fechaVtoPago"]);
                $expiration = "- " . $this->lang("Vencimiento") . ": " . date_format($tmp, $this->lang('d/m/Y'));
                $this->html .= "<br>";
                $this->html .= "    <span class='header_margin'>$service_from</span>";
                $this->html .= "    <span class='header_margin'>$service_to</span>";
                $this->html .= "    <span class='header_margin'>$expiration</span>";
            }

            $this->html .= "</td>";
            $this->html .= "</tr>";
            $this->html .= "</table>";

            $this->html .= "<table class='responsive-table table-header'>";
            $this->html .= "<tr>";
            $this->html .= "<td style='width:50%;'>" . $this->lang("Raz&oacute;n social") . ": " . strtoupper($this->config["TRADE_SOCIAL_REASON"]) . "</td>";
            $this->html .= "<td class='right-text' style='width:49%;'>" . $this->lang("CUIT") . ": " . $this->config["TRADE_CUIT"] . "</td>";
            $this->html .= "</tr>";
            $this->html .= "<tr>";
            $this->html .= "<td style='width:50%;'>" . $this->lang("Domicilio comercial") . ": " . strtoupper($this->config["TRADE_ADDRESS"]) . "</td>";
            $this->html .= "<td class='right-text' style='width:49%;'>" . $this->lang("Ingresos Brutos") . ": " . $this->config["TRADE_CUIT"] . "</td>";
            $this->html .= "</tr>";
            $this->html .= "<tr>";
            $this->html .= "<td style='width:50%;'>" . $this->lang("Condici&oacute;n frente al IVA") . ": " . strtoupper($this->config["TRADE_TAX_CONDITION"]) . "</td>";
            $tmp = DateTime::createFromFormat('d/m/Y',$this->config["TRADE_INIT_ACTIVITY"]);
            $this->html .= "<td class='right-text' style='width:49%;'>" . $this->lang("Fecha de inicio de actividades") . ": " . date_format($tmp, $this->lang('d/m/Y')) . "</td>";
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
            $this->html .= "<tr>";
            $text = $this->lang("Condici&oacute;n frente al IVA") . ": " . $this->lang($this->voucher["tipoResponsable"]);
            $this->html .= "<td style='width:50%;'>" . $text . "</td>";
            $text = $this->lang("Domicilio") . ": " . $this->voucher["domicilioCliente"];
            $this->html .= "<td class='right-text' style='width:49%;'>" . $text . "</td>";
            $this->html .= "</tr>";
            $this->html .= "<tr>";
            $text = $this->lang("Condici&oacute;nes de venta") . ": " . $this->lang($this->voucher["CondicionVenta"]);
            $this->html .= "<td style='width:10%;'>" . $text . "</td>";
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
        if (strtoupper($this->voucher["letra"]) === 'A') {
            $this->fill_A();
        } else {
            $this->fill_B();
        }
        $this->html .= "</table>";
        $this->finished = true;
    }

    /**
     * Imprime el detalle para comprobantes tipo A
     * 
     * @author NeoComplexx Group S.A.
     */
    function fill_A() {
        $this->html .= "<tr>";
        $this->html .= "<th class='center-text' style='width=10%;'>" . $this->lang("C&oacute;digo") . "</th>";
        $this->html .= "<th style='width=26%;'>" . $this->lang("Producto / Servicio") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Cantidad") . "</th>";
        $this->html .= "<th style='width=10%;'>" . $this->lang("U. Medida") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Precio unit.") . "</th>";
        $this->html .= "<th class='right-text' style='width=8%;'>" . $this->lang("% Bonif") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Imp. Bonif.") . "</th>";
        $this->html .= "<th class='right-text' style='width=6%;'>" . $this->lang("IVA") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Subtotal") . "</th>";
        $this->html .= "</tr>";
        foreach ($this->voucher["items"] as $item) {
            $this->html .= "<tr>";
            if (isset($this->config["TYPE_CODE"]) && $this->config["TYPE_CODE"] == 'scanner') {
                $this->html .= "<td class='center-text' style='width=10%;'>" . $item["scanner"] . "</td>";
            } else {
                $this->html .= "<td class='center-text' style='width=10%;'>" . $item["codigo"] . "</td>";
            }
            $this->html .= "<td style='width=26%;'>" . $item["descripcion"] . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["cantidad"], 3) . "</td>";
            $this->html .= "<td style='width=10%;'>" . $item["UnidadMedida"] . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["precioUnitario"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=8%;'>" . number_format($item["porcBonif"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["impBonif"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=6%;'>" . number_format($item["Alic"], 0) . "%</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["importeItem"], 2) . "</td>";
            $this->html .= "</tr>";
        }
    }

    /**
     * Imprime el detalle para comprobantes tipo B
     * 
     * @author NeoComplexx Group S.A.
     */
    function fill_B() {
        $this->html .= "<tr>";
        $this->html .= "<th class='center-text' style='width=10%;'>" . $this->lang("C&oacute;digo") . "</th>";
        $this->html .= "<th style='width=30%;'>" . $this->lang("Producto / Servicio") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Cantidad") . "</th>";
        $this->html .= "<th style='width=10%;'>" . $this->lang("U. Medida") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Precio unit.") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("% Bonif") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Imp. Bonif.") . "</th>";
        $this->html .= "<th class='right-text' style='width=10%;'>" . $this->lang("Subtotal") . "</th>";
        $this->html .= "</tr>";
        foreach ($this->voucher["items"] as $item) {
            $this->html .= "<tr>";
            if (isset($this->config["TYPE_CODE"]) && $this->config["TYPE_CODE"] == 'scanner') {
                $this->html .= "<td class='center-text' style='width=10%;'>" . $item["scanner"] . "</td>";
            } else {
                $this->html .= "<td class='center-text' style='width=10%;'>" . $item["codigo"] . "</td>";
            }
            $this->html .= "<td style='width=30%;'>" . $item["descripcion"] . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["cantidad"], 3) . "</td>";
            $this->html .= "<td style='width=10%;'>" . $this->lang($item["UnidadMedida"]) . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["precioUnitario"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["porcBonif"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["impBonif"], 2) . "</td>";
            $this->html .= "<td class='right-text' style='width=10%;'>" . number_format($item["importeItem"], 2) . "</td>";
            $this->html .= "</tr>";
        }
    }

    /**
     * Imprime la linea de totales
     *
     * @author NeoComplexx Group S.A.
     */
    function total_line() {
        if ($this->show_element("total_line")) {
            $this->html .= "<div class='border-div'>";
            if (strtoupper($this->voucher["letra"]) === 'A') {
                $this->total_line_A();
            } else {
                $this->total_line_B();
            }
            $this->html .= "</div>";
        }
    }

    /**
     * Imprime la linea de totales para comprobantes con letra A
     *
     * @author NeoComplexx Group S.A.
     */
    private function total_line_A() {
        $this->html .= '    <table class="responsive-table">';
        $this->html .= '        <tr>';
        $this->html .= '            <td style="width: 60%;">';
        $this->html .= $this->othertaxes();
        $this->html .= '            </td>';
        $this->html .= '            <td style="width: 40%;">';
        $this->html .= $this->ivas();
        $this->html .= '            </td>';
        $this->html .= '        </tr>';
        $this->html .= '    </table>';
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

    /**
     * Retorna la tabla de ivas y totales
     *
     * @return string
     *
     * @author NeoComplexx Group S.A.
     */
    private function ivas() {
        $str = '    <table class="responsive-table">';

        //Detail
        $str .= '        <tr>';
        $str .= '            <td class="right-text" style="width=200px;">' . $this->lang("Importe neto gravado") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
        $importeGravado = number_format((float) round($this->voucher["importeGravado"], 2), 2, '.', '');
        $str .= '            <td class="right-text" style="width=70px;">' . $importeGravado . '</td>';
        $str .= '        </tr>';
        foreach ($this->voucher["subtotivas"] as $iva) {
            $value = $iva["Alic"];
            $descripcion = "IVA $value%: " . $this->lang($this->voucher["codigoMoneda"]);
            $importe = number_format((float) round($iva["importe"], 2), 2, '.', '');
            $str .= '        <tr>';
            $str .= '            <td class="right-text" style="width=200px;">' . $descripcion . '</td>';
            $str .= '            <td class="right-text" style="width=70px;">' . $importe . '</td>';
            $str .= '        </tr>';
        }

        //Footer
        $str .= '        <tr>';
        $str .= '            <td class="right-text" style="width=200px;">' . $this->lang("Importe otros tributos") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
        $importeOtrosTributos = number_format((float) round($this->voucher["importeOtrosTributos"], 2), 2, '.', '');
        $str .= '            <td class="right-text" style="width=70px;">' . $importeOtrosTributos . '</td>';
        $str .= '        </tr>';
        $str .= '        <tr>';
        $str .= '            <td class="right-text" style="width=200px;">' . $this->lang("Importe total") . ': '. $this->lang($this->voucher["codigoMoneda"]) .'</td>';
        $importeTotal = number_format((float) round($this->voucher["importeTotal"], 2), 2, '.', '');
        $str .= '            <td class="right-text" style="width=70px;">' . $importeTotal . '</td>';
        $str .= '        </tr>';
        $str .= '    </table>';

        return $str;
    }

    /**
     * Imprime la linea de totales para comprobantes con letra B
     *
     * @author: NeoComplexx Group S.A.
     */
    private function total_line_B() {
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
            $this->html .= '    <table class="responsive-table page_footer">';
            if ($this->voucher["cae"] != 0) {
                $text_left = $this->lang("Comprobante Autorizado");
                $text_1 = $this->lang("CAE") .": ";
                $text_2 = $this->voucher["cae"];
                $text_3 = $this->lang("Fecha Vto. CAE") .": ";
            
                $tmp = DateTime::createFromFormat('Y-m-d',$this->voucher["fechaVencimientoCAE"]);
                $text_4 = date_format($tmp, $this->lang('d/m/Y'));

                $quotation = number_format((float) round($this->voucher["cotizacionMoneda"], 2), 2, '.', '');
                $text_5 = $this->lang("Moneda") . ": " . $this->lang($this->voucher["codigoMoneda"]) . " | " . $this->lang("Cotizaci&oacute;n") . ": " . $quotation;
            } else {
                $text_left = $this->lang("Documento no v&aacute;lido como factura");
                $text_1 = "&nbsp;";
                $text_2 = "&nbsp;";
                $text_3 = "&nbsp;";
                $text_4 = "&nbsp;";
                $text_5 = "&nbsp;";
            }
            $this->html .= '        <tr>';
            $this->html .= '		<td class="" style="width: 30%;">' . $text_left . "</td>";
            $this->html .= '		<td class="center-text" style="width: 40%;">';
            $this->html .= '                ' . $this->lang("Pag.") . ' [[page_cu]]/[[page_nb]]';
            $this->html .= '		</td>';
            $this->html .= '		<td class="right-text" style="width: 15%;">' . $text_1 . "</td>";
            $this->html .= '		<td class="left-text" style="width: 15%;">' . $text_2 . "</td>";
            $this->html .= '        </tr>';

            $this->html .= '        <tr>';
            $this->html .= '		<td class="" style="width: 30%;">' . $text_5 . "</td>";
            $this->html .= '		<td class="center-text" style="width: 40%;">&nbsp;</td>';
            $this->html .= '		<td class="right-text" style="width: 15%;">' . $text_3 . "</td>";
            $this->html .= '		<td class="left-text" style="width: 15%;">' . $text_4 . "</td>";
            $this->html .= '        </tr>';

            $this->html .= '    </table>';

            if ($this->voucher["cae"] != 0) {
                //BARCODE
                $cuit = str_replace("-", "", $this->config["TRADE_CUIT"]);
                $pos = str_pad($this->voucher["numeroPuntoVenta"], 4, "0", STR_PAD_LEFT);
                $type = str_pad($this->voucher["codigoTipoComprobante"], 2, "0", STR_PAD_LEFT);
                $cae = $this->voucher["cae"];
                $fecha = str_replace("-", "", $this->voucher["fechaVencimientoCAE"]);

                $barcode_number = $cuit . $type . $pos . $cae . $fecha;

                $this->html .= '<barcode type="I25+" value="' . $barcode_number . '" label="label" style="width:20cm; height:7mm; font-size: 7px"></barcode>';
            }
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
        $this->html .= "<page>";
        //DUPLICADO
        $this->addVoucherInformation($logo_path, $this->lang("DUPLICADO"));
        $this->addReceiverInformation();
        $this->fill();
        $this->footer();
        $this->html .= "</page>";
        $this->WriteHTML($this->html);
    }

}