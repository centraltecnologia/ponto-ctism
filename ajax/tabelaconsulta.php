<?php
/**
 * Created by PhpStorm.
 * User: brlaranjeira
 * Date: 11/16/17
 * Time: 10:58 AM
 */

require_once (__DIR__.'/../dao/Ponto.php');
require_once (__DIR__.'/../dao/Usuario.php');
$bolsista = $_REQUEST['bolsista'];
$bolsista = (new Usuario($bolsista))->getUidnumber();
$ano = $_REQUEST['ano'];
$mes = $_REQUEST['mes'];

$dtIni= "$ano-$mes-01 00:00:00";
$anoFim = ($mes != '12') ? $ano : intval($ano)+1;
$mesFim = ($mes != '12') ? str_pad(intval($mes)+1,2,0,STR_PAD_LEFT) : '01';
$dtFim = "$anoFim-$mesFim-01 00:00:00";


$pontos = Ponto::getByAttr (
	array('usuario','timestamp','timestamp'),
	array($bolsista,$dtIni,$dtFim),
	array('=','>=','<'),
	array(
		'DATE(' . Ponto::getColumnName('timestamp') . ')',
		Ponto::getColumnName('event') . '= \'' . Ponto::PONTO_ABONO . '\'',
		'TIME('. Ponto::getColumnName('timestamp'). ')'));

if (empty($pontos)) {
	echo '<tr><td colspan="4">Nenhum registro encontrado</td></tr>';
}

$anterior = null;
$totalTrab = 0;
$totalAbono = 0;
foreach ( $pontos as $ponto ) {
	
	$hora = $ponto->getTimestamp( Ponto::TS_HORARIO );
	$btnDelete = '<button class="btn-delete btn btn-danger btn-small"' . 'cod="' . $ponto->getId() . '"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>';
	$hora .= " $btnDelete";
	$data = $ponto->getTimestamp( Ponto::TS_DATA );
	
	if ( $ponto->getEvent() == Ponto::PONTO_ENTRADA ) {
		if ( ( isset( $anterior ) && $anterior->getEvent() == Ponto::PONTO_ENTRADA ) )   {
			echo '<td>Justifique sua saida</td></tr>';
		}
		echo "<tr><td>$data</td><td>$hora</td>";
	} elseif ( $ponto->getEvent() == Ponto::PONTO_SAIDA ) {
		$pendencia = false;
		if ( !isset( $anterior ) || $anterior->getEvent() == Ponto::PONTO_SAIDA ) {
			echo "<tr><td>$data</td><td>Justifique sua Entrada</td>";
			$pendencia = true;
		} elseif ( isset( $anterior ) && $anterior->getEvent() == Ponto::PONTO_ENTRADA && $ponto->getTimestamp( Ponto::TS_DATA ) != $anterior->getTimestamp( Ponto::TS_DATA ) ) {
			echo "<td>Justifique sua Saida</td><td>Impossível calcular</td></tr>";
			echo "<tr><td>$data</td><td>Justifique sua entrada</td>";
			$pendencia = true;
		}
		echo "<td>$hora</td>";
		if ($pendencia) {
			echo '<td>Impossível calcular</td>';
		} else {
			require_once (__DIR__ . '/../lib/Utils.php');
			$diff = Utils::timeDiff($ponto->getTimestamp(Ponto::TS_HORARIO),$anterior->getTimestamp(Ponto::TS_HORARIO));
			$totalTrab += $diff;
			$tempo = Utils::secondsToStrtime($diff);
			echo "<td>$tempo</td>";
		}
		echo '</tr>';
		$anterior = $ponto;
	}
	$anterior = $ponto->getEvent() == Ponto::PONTO_ABONO ? $anterior : $ponto;
} ?>