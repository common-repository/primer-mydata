<?php

// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }


if ( ! function_exists( 'primer_get_receipt_label' ) ) :
	function primer_get_receipt_label() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-label'] ) ? $translate['receipt-label'] : __( 'Receipt', 'primer');
		return apply_filters( 'primer_get_receipt_label', $label );
	}
endif;

if ( ! function_exists( 'primer_get_receipt_label_plural' ) ) :
	function primer_get_receipt_label_plural() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-label-plural'] ) ? $translate['receipt-label-plural'] : __( 'Receipts', 'primer');
		return apply_filters( 'primer_get_receipt_label_plural', $label );
	}
endif;


if ( ! function_exists( 'primer_get_receipt_log_label' ) ) :
	function primer_get_receipt_log_label() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-log-label'] ) ? $translate['receipt-log-label'] : __( 'Receipt Log', 'primer');
		return apply_filters( 'primer_get_receipt_log_label', $label );
	}
endif;

if ( ! function_exists( 'primer_get_receipt_log_label_plural' ) ) :
	function primer_get_receipt_log_label_plural() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-log-label-plural'] ) ? $translate['receipt-log-label-plural'] : __( 'Receipts Log', 'primer');
		return apply_filters( 'primer_get_receipt_log_label_plural', $label );
	}
endif;

if ( ! function_exists( 'primer_get_receipt_log_automation_label' ) ) :
	function primer_get_receipt_log_automation_label() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-log-automation-label'] ) ? $translate['receipt-log-automation-label'] : __( 'Automation Log', 'primer');
		return apply_filters( 'primer_get_receipt_log_automation_label', $label );
	}
endif;

if ( ! function_exists( 'primer_get_receipt_log_automation_label_plural' ) ) :
	function primer_get_receipt_log_automation_label_plural() {
		$translate = get_option( 'primer_translate' );
		$label = isset( $translate['receipt-log-automation-label-plural'] ) ? $translate['receipt-log-automation-label-plural'] : __( 'Automation Logs', 'primer');
		return apply_filters( 'primer_get_receipt_log_automation_label_plural', $label );
	}
endif;
