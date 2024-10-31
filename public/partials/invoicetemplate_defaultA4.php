<!DOCTYPE html>
<html>

<head lang="en">
    <title>template_default_A4</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
            }

            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: always;
            }
        }

        .page {
            width: 210mm;
            height: 297mm;
            border: 1px solid #ddd;
        }

        body {

            margin: 0;
            padding: 0;
            /* filter: grayscale(100%); */
        }


        .invoice-box {


            margin-left: 20px;
            margin-right: 20px;
            font-size: 10px;
            font-family: 'Helvetica Neue', Helvetica, Helvetica, Arial, sans-serif;
            color: #555;
            /* border: 1px solid #ddd; */
            /* padding-bottom: 40px; */
            width: calc(100% - 40px);
            height: calc(100% - 20px);
            position: relative;
        }

        .qrcode_img {
            position:absolute;
            left:276px;
            margin: auto;

        }

        .qrcodeimg_container {
            min-width: 180px;
            max-width: 180px;
            margin: 0px;
            padding: 0px;
            padding-right: 12px;
        }

        .logo_img {
            width: 100%;
            max-width: 350px;
            height: 100px;
        }


        /* ------------------totals------------------ */

        .total_container {
            margin-top: 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 10px;
            /* margin-right: 6px; */
        }

        .total_container>.totals {

            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 4px;
            background-color: #555;
        }

        .total_container>.totals p {

            padding: 4px;
            margin: 4px;
            font-size: 12px;
        }

        .totals_table {
            border-spacing: 0;
            border-collapse: collapse;
            border: 0;
        }

        .totals_table tr {
            padding: 16px;
            border: none;
        }

        .totals_table td {
            border: none;

        }

        .totalpayment {
            font-weight: 700;
            font-size: 16px;
        }

        /* ------------------end totals------------------ */

        .invoice-box table {
            width: 100%;
            text-align: left;

        }

        .invoice-box table td {
            padding: 1px;
        }



        .invoice-box table tr.top table td {
            padding: 10px
        }

        .invoice-box table tr.top table td.logo_container {
            font-size: 30px;
            width: 50%;
            color: #333
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px
        }

        .main_info_table tr {
            border: 1px solid #ddd;
            font-weight: 700;
            text-align: center;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee
        }

        .invoice-box table tr.item.last td {
            border-bottom: none
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: 700
        }

        .products {
            border: 1px solid #eee;
            text-align: center;

        }

        .products p {
            height: 24px;
            font-size: 12px;
            margin: 4px;
        }

        .product_table .products td {
            font-size: 10px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .product_table .heading td {
            font-size: 10px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .main_info_table .heading p {
            margin: 4px;
        }

        .product_container {
            margin-top: 4px;
            border: 2px solid #555;
            border-radius: 4px;
            overflow: hidden;
        }

        .rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', Helvetica, Helvetica, Arial, sans-serif
        }

        .rtl table {
            text-align: right
        }

        .rtl table tr td:nth-child(2) {
            text-align: left
        }

        .sender_sign {
            position: absolute;
            bottom: 20px;
            left: 0px;
        }

        .mydata_sign {
            position: absolute;
            float: right
        }

        .pol_number {
            float: right;
        }

        .heading {
            background-color: #555;
            color: white;
        }

        .heading>td {

            height: 4px;
        }

        .information {

            border: 1px solid #555;
            border-radius: 4px;
            padding: 10px;

        }

        .main_info {

            border: 1px solid #555;
            border-radius: 4px;
            overflow: hidden;

        }

        .skin {
            color: #555;
        }

        .bold {
            color: #555;
            font-weight: bold;
        }

        .footer_container {
            position: absolute !important;
            bottom: 10px;
            /* border: 1px solid #ddd; */
            width: 100%;
            margin: auto;
            padding-bottom: 10px;
        }


        .header_table td {
            border: none;

        }

        .issuer_container {
            text-align: center;
            margin-top: 6px;
        }

        .issuer_container .issuer_name {
            font-size: 14px;
            font-weight: bold;
        }

        .issuer_container .issuer_subjectField {
            font-weight: bold;
            font-style: italic;
        }

        .issuer_container p {
            margin: 0px;
            font-size: 10px;
        }

        .gemh_issuer_p {
            font-style: italic;
        }

        .information_table {
            margin-top: 4px;

        }

        .information_table td {
            padding: 2px !important;
            border: none;
            font-size: 12px;
        }

        .code_head_td {
            width: 14%;
        }

        .description_head_td {
            width: 32%;
        }

        .price_head_td {
            width: 8%;
        }

        .vat_head_td {
            width: 8%;
        }

        .blank_row.bordered td {
            border-top: 1px solid white;
            background-color: white;
            max-height: 2px;
            height: 2px;
            line-height: 2px;
        }

        .text-right {
            text-align: right;
            margin-right: 20px;
            background-color: white;
        }

        .text-left {
            background-color: #555;
            color: white;
        }

        .info_value {
            font-weight: bold;
        }

        .cont_notation {
            border: 1px solid #555;
            padding: 8px;
            border-radius: 8px;
            overflow: hidden;
            height: 68px;
            margin-top: 10px;
        }

        .cont_signs {
            border: 1px solid #555;
            padding: 8px;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px;
        }

        .footer_table td {
            vertical-align: top;
        }

        .per_vat_totals_container {
            border: 1px solid #555;
            border-radius: 8px;
            margin-top: 10px;
            padding: 12px;
        }

        .totals_per_vat th {
            width: 10%;
            color: #555;
            font-weight: bold;
        }

        .total_funny_box {
            width: 80px;
            height: 46px;
            background-color: #555;
            border: 1px solid white;
            border-radius: 0px 0px 8px 0px;
            position: absolute;
            bottom: 60px;
            right: -2px;
            z-index: -1;
            display: none;
        }

        .union_doc_sign {
            position: absolute;
            transform: rotate(-90deg);
            left: -164px;
            bottom: 560px;
            font-size: 11px;
            margin: 0px;

        }

        .count_totals_container {
            padding: 4px;
            border: 4px solid #555;
            border-radius: 8px;
            min-height: 16px;
            max-height: 16px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .count_total_prods {
            font-size: 16px;
            font-weight: bold;
        }

        .cont_sign_left {
            float: left;
            text-align: center;
            width: 50%;
            font-size: 12px;
        }

        .cont_sign_right {
            float: right;
            text-align: center;
            width: 50%;
            font-size: 12px;
        }

        .fullname_sign {
            font-size: 9px;
        }

        .sign_hr {
            margin: 0px;
            width: 80%;
            margin-left: 10%
        }

        .finalprice p {
            font-weight: bold;
            font-size: 16px !important;
        }

        .information_td_left {
            width: 49%;
            font-size: 12px;

        }

        .information_td_right {
            width: 49%;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="invoice-box">
            <div class="top_table">
                <table>
                    <tbody>
                        <tr class="top">
                            <td>
                                <table class="header_table">
                                    <tr>
	                                    <?php
	                                    $img_src = isset($_GET['img_src']) ? $_GET['img_src'] : '';
	                                    ?>
										<td class="logo_container">
		                                    <?php
											if (isset($_GET['primer_use_logo'])) {
			                                    if ($_GET['primer_use_logo'] == 'on') { ?>
													<img src="<?php echo htmlspecialchars($img_src); ?>" alt=""  class="logo_img">
			                                    <?php }
		                                    } ?>
										</td>

                                        <td class="issuer_container">
                                            <span class="issuer_name skin">{ISSUER_NAME}</span>
                                            <p> <span class="issuer_subjectField skin">{ISSUER_SUBJECTFIELD}</span></p>
                                            <p><span class="issuer_address skin">{ISSUER_ADDRESS}</span></p>

                                            <p> <span class="skin">ΑΦΜ: </span><span class="issuer_vat skin">{ISSUER_VAT}</span> <span class="skin">ΔΟΥ: </span> <span class="issuer_doy skin">{ISSUER_DOY}</span></p>
                                            <p class="gemh_issuer_p skin"> <span class="skin">ΑΡ.ΓΕΜΗ: </span> <span class="issuer_gemh">{ISSUER_GEMH}</span></p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="main_info">
                    <table>
                        <tbody>
                            <tr>
                                <td>
                                    <table class="main_info_table">
                                        <tbody>
                                            <tr class="heading">
                                                <td>
                                                    <p>INVOICE TYPE</p>
                                                </td>

                                                <td>
                                                    <p>INVOICE NUMBER</p>
                                                </td>
                                                <td>
                                                    <p>DATE</p>
                                                </td>
                                                <td>
                                                    <p>TIME</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <span class="invoice_type">{INVOICE_TYPE}</span>
                                                </td>

                                                <td>
                                                    <span class="invoice_number">{INVOICE_NUMBER}</span>
                                                </td>
                                                <td>
                                                    <span class="invoice_date"> {INVOICE_DATE}</span>
                                                </td>
                                                <td>
                                                    <span class="invoice_time"> {INVOICE_TIME}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <table class="information_table">
                    <tbody>
                        <tr>
                            <td class="information_td_left">
                                <div class="information left">
                                    <table>
                                        <tr>
                                            <td class="skin bold">
                                                <span> CODE</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_code">{CP_CODE}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span> NAME</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_name">{CP_NAME}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>PROFESSION</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_activity"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>VAT</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_vat">{CP_NAME}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>DOY</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_doy"></span>
                                            </td>
                                        </tr>
                                        <tr class="blank_row">
                                            <td>&nbsp;</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td> </td>
                            <td class="information_td_right">
                                <div class="information right">
                                    <table>
                                        <tr>
                                            <td class="skin bold">
                                                <span>PAYMENT TYPE</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_paytype">{CP_PAYTYPE}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span> TRADING PURPOSE</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_tradepurpose">{CP_TRADEPURPOSE}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>CITY</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_city"></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>ADDRESS</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="counterparty_address">{CP_ADDRESS}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="skin bold">
                                                <span>SEND PLACE</span>
                                            </td>
                                            <td class="info_value">
                                                <span>: </span>
                                                <span class="send_place">{SEND_PLACE}</span>
                                            </td>
                                        </tr>
                                        <tr class="blank_row">
                                            <td>&nbsp;</td>
                                        </tr>

                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="product_container">
                <table class="product_table">
                    <tr class="heading">
                        <td class="code_head_td">
                            <p> PRODUCT CODE</p>
                        </td>
                        <td class="description_head_td">
                            <p> DESCRIPTION</p>
                        </td>
                        <td class="quantity_head_td">
                            <p> QUANTITY</p>
                        </td>
                        <td class="mu_head_td">
                            <p> MEASURE UNIT</p>
                        </td>
                        <td class="up_head_td">
                            <p> UNIT PRICE</p>
                        </td>
                        <td class="disc_head_td">
                            <p> DISCOUNT</p>
                        </td>
                        <td class="whtax_head_td">
                            <p> WITHHOLDING TAXES</p>
                        </td>
                        <td class="vat_head_td">
                            <p> VAT %</p>
                        </td>
                        <td class="pricenovat_head_td">
                            <p> NET VALUE</p>
                        </td>
                        <td class="price_head_td">
                            <p> TOTAL PRICE</p>
                        </td>
                    </tr>

                    <tr class="products">
                        <td>
                            <span class="item_code">
                                {CODE}
                            </span>
                        </td>
                        <td>
                            <span class="item_name">
                                {NAME_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_quantity">
                                {QUANTITY_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_mu">
                                {MU_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_unit_price">
                                {UP_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_discount">
                                {DISCOUNT}
                            </span>
                        </td>
                        <td>
                            <span class="item_whtaxes">
                                {WHTAXES}
                            </span>
                        </td>
                        <td>
                            <span class="item_vat">
                                {VAT_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_price_novat">
                                {PRICE_NOVAT_LIST}
                            </span>
                        </td>
                        <td>
                            <span class="item_price_withvat">
                                {PRICE_LIST}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="footer_container">

                <div class="per_vat_totals_container">
                    <!-- <p class="card-title">Σύνολα ανα ΦΠΑ</p> -->
                    <table class="totals_per_vat"> </table>
                </div>
                <table class="footer_table">
                    <tbody>
                        <tr>

                            <td>
                                <div class="cont_notation"><span class="skin bold">COMMENTS:</span>
                                    <div class="cont_notation_inner">
                                        <span class="notes">{NOTES}</span>
                                    </div>
                                </div>
                                <div class="cont_signs">
                                    <div class="cont_sign_left">
                                        <span class="sign_left">ISSUER</span>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        <hr class="sign_hr">
                                        <span class="fullname_sign">FULL NAME SIGNATURE</span>
                                    </div>
                                    <div class="cont_sign_right">
                                        <span class="sign_right">RECEIPTIENT</span>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        <hr class="sign_hr">
                                        <span class="fullname_sign">FULL NAME <BR>SIGNATURE</span>
                                    </div>
                                </div>
                            </td>
                            <td class="qrcodeimg_container">
                                <span class="qrcode_img"></span>
                            </td>
                            <td>
                                <div class="count_totals_container">
                                    <span>SUM OF UNITS: </span> <span class="count_total_prods"></span>
                                </div>
                                <div class="total_container">



                                    <div class="totals">
                                        <table class="totals_table">
                                            <tr>
                                                <td class="text-left">
                                                    <p>TOTAL NO DISCOUNT</p>
                                                </td>
                                                <td class="text-right">
                                                    <p><span class="total_nodiscount">{TOTALNODISCOUNT}</span> </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-left">
                                                    <p>TOTAL DISCOUNT</p>
                                                </td>
                                                <td class="text-right">
                                                    <p><span class="total_discount">{TOTALDISCOUNT}</span></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-left">
                                                    <p>TOTAL WITHOUT VAT</p>
                                                </td>
                                                <td class="text-right">
                                                    <p><span class="total_withoutvat">{TOTALWITHOUTVAT}</span> </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-left">
                                                    <p>TOTAL SUM</p>
                                                </td>
                                                <td class="text-right">
                                                    <p><span class="amounttotal">{TOTALWITHVAT}</span> </p>
                                                </td>
                                            </tr>
                                            <tr class="blank_row bordered">
                                                <td class="text-left">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td class="text-left finalprice">
                                                    <p>TOTAL PAYMENT</p>
                                                </td>
                                                <td class="text-right">
                                                    <p><span class="totalpayment">{TOTALPAYMENT}</span> </p>
                                                </td>
                                            </tr>
                                        </table>
                                        <div class="total_funny_box"></div>
                                    </div>


                                </div>

                            </td>

                        </tr>
                    </tbody>
                </table>


                <p> <span class="sender_sign">https://primer.gr/searchinvoice <br>Provided by Primer Software P.C.</span></p><br><br>
                <p class="mydata_sign">
                    <span>uid: </span> <span class="uid_sign">{INVOICEUID}</span>
                    <span>mark:</span> <span class="mark_sign">{INVOICEMARK}</span>
                    <span>authcode:</span> <span class="authcode_sign">{AUTHCODE}</span>
                </p>
            </div>

            <p class="union_doc_sign skin">ΕΝΙΑΙΟ ΜΗΧΑΝΟΓΡΑΦΙΚΟ ΕΝΤΥΠΟ ΠΟΛΛΑΠΛΩΝ ΧΡΗΣΕΩΝ</p>
        </div>

    </div>
</body>

</html>
