<style type="text/css">
<!--
.motivo_otro_hidden{
	visibility:hidden;
}
.motivo_otro_visible{
	visibility:visible;
}
//-->
</style>
<script language="javascript" type="text/javascript">
	function otroMotivo(selectTag){
		if(selectTag.value != 'Otro motivo'){
			document.getElementById('motivo_otro').setAttribute("class","motivo_otro_hidden");
		}else{
			document.getElementById('motivo_otro').setAttribute("class","motivo_otro_visible");
		}
	}
</script>
<?php

### Load WP-Config File If This File Is Called Directly
if (!function_exists('add_action')) {
	$wp_root = '../../..';
	
	if (file_exists($wp_root.'/wp-load.php')) {
		require_once($wp_root.'/wp-load.php');
	} else {
		require_once($wp_root.'/wp-config.php');
	}
}


### Use WordPress 2.6 Constants
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH.'wp-content');
if ( !defined('WP_CONTENT_URL') )
	define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');

// Cogemos la ruta
$event_change_status__wp_dirname = basename(dirname(dirname(__FILE__))); // for "plugins" or "mu-plugins"
$event_change_status__pi_dirname = basename(dirname(__FILE__)); // plugin name

$event_change_status__path = WP_CONTENT_DIR.'/'.$event_change_status__wp_dirname.'/'.$event_change_status__pi_dirname;
$event_change_status__url = WP_CONTENT_URL.'/'.$event_change_status__wp_dirname.'/'.$event_change_status__pi_dirname;



//Rechazamos el evento y le enviamos un email al solicitante del evento 
//comunicandole que se ha rechazado y el motivo de dicha decisión.
if ( (isset($_POST['action']) && ($_POST['action'] == 'refuse')) &&  (isset($_POST['event_id']) && ($_POST['event_id'] != '')) &&  (isset($_POST['event_email']) && $_POST['event_email'] != '') )
{
	global $wpdb;

	$event_id = !empty($_POST['event_id']) ? $_POST['event_id'] : '';
	$event_email = !empty($_POST['event_email']) ? $_POST['event_email'] : '';
	$motivo = !empty($_POST['motivo']) ? $_POST['motivo'] : '';
	$motivo_otro = !empty($_POST['motivo_otro']) ? $_POST['motivo_otro'] : '';
	
	//Comprobamos que no estuviera ya rechazado
	$check = $wpdb->get_results("SELECT * FROM ". WP_CALENDAR_TABLE_Aula . " WHERE event_id = '" . $event_id . "' AND event_valid = 'r' LIMIT 1");
	if(empty($check))
	{
		$last_update = date("Y-m-d");
		
		//Rechazamos el evento
		$wpdb->get_results("UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_valid = 'r',event_update='".$last_update."' WHERE event_id ='" . $event_id . "'");
	
		// Enviamos el email al usuario que solicitó el evento
		$event = $wpdb->get_results("SELECT * FROM ". WP_CALENDAR_TABLE_Aula . " WHERE event_id = '" . $event_id . "' LIMIT 1");
		$event = $event[0];	
				//Cuerpo del email
					$notify_message1_solicitante = __('Lamentamos comunicarle que su solicitud ha sido denegada por el siguiente motivo: ', 'event-change-status') ."\r\n\n";
					if($motivo != 'Otro motivo')
						$notify_message1_solicitante .= $motivo."\r\n\n";
					else
						$notify_message1_solicitante.= $motivo_otro."\r\n\n";
					$notify_message .= __('Email del solicitante: ', 'event-change-status') . $event->event_email . "\r\n";
					$notify_message .= __('Teléfono: ', 'event-change-status') . $event->event_phone . "\r\n";
					$notify_message .= __('Dni: ', 'event-change-status') . $event->event_phone . "\r\n";
					$notify_message .= __('Título del Evento: ', 'event-change-status') . $event->event_title . "\r\n";
					$notify_message .= __('Descripción: ', 'event-change-status') . $event->event_desc . "\r\n\r\n";
					$notify_message .= __('Aula: ', 'event-change-status') . $result_category[0]->category_name . "\r\n";
					$notify_message .= __('Centro/Departamento: ', 'event-change-status') . $event->event_department . "\r\n";
					$notify_message .= __('Sector: ', 'event-change-status') . $event->event_sector . "\r\n";
					$notify_message .= __('Material necesario: ', 'event-change-status');
					if($event->event_material != '')
					{
						list( $pc, $videoproyector, $megafonia, $otros ) = split( ';', $event->event_material );
						if($pc == 'PC')
							$notify_message .= __(' PC, ', 'event-change-status');
						if($videoproyector == 'Videoproyector')
							$notify_message .= __(' Videoproyector, ', 'event-change-status');
						if($megafonia == 'Megafonía')
							$notify_message .= __(' Megafonía, ', 'event-change-status');
						if($otros == 'Otros')
							$notify_message .= __(' Otros.', 'event-change-status');
					}	
					$notify_message .= "\r\n\r\n";
					$notify_message .= __('Observaciones: ', 'event-change-status') . $event->event_obs . "\r\n\r\n";
					$notify_message .= __('Fecha Inicio: ', 'event-change-status') . $event->event_begin . "\r\n";
					$notify_message .= __('Fecha Fin: ', 'event-change-status') . $event->event_end . "\r\n\r\n";
					$notify_message .= __('Hora Inicio: ', 'event-change-status') . $event->event_time_begin . "\r\n";
					$notify_message .= __('Hora Fin: ', 'event-change-status') . $event->event_time_end . "\r\n\r\n";
					
				//Cabecera del email
					$from_solicitante = "From: \"Facultad de Psicologia\" <no-reply@no-reply.org>";
					$reply_to_solicitante = "Reply-To: no-reply@no-reply.org";
					$message_headers_solicitante = "$from_solicitante\n"
						. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
					if ( isset($reply_to_solicitante) )
						$message_headers_solicitante .= $reply_to_solicitante . "\n";
					$message_solicitante = $notify_message1_solicitante.$notify_message;
		
		wp_mail($event_email, "Su solicitud ha sido rechazada", $message_solicitante, $message_headers_solicitante);	
					
		wp_die(__("Evento rechazado correctamente, se ha enviado un email al solicitante comunicandole dicha decisión y los motivos.", 'event-change-status'), '', 'response=200');
	}
	else
	{
		wp_die(__('El evento ya ha sido visitado. ;-)', 'event-change-status'), '', 'response=200');
	}
	
}

if ( isset($_GET['becid']) && $_GET['becid'] != '' ) {

	function event_change_status__process() {
		global $wpdb;

		// Check IP From IP Logging Database
		$get_event_by_md5ID = $wpdb->get_results("SELECT * FROM ". WP_CALENDAR_TABLE_Aula . " WHERE event_id = '" . $_GET['becid'] . "' LIMIT 1");
		$event = $get_event_by_md5ID[0];

		if ( !empty($get_event_by_md5ID)) {

			$message = __('Hi!?!', 'event-change-status');
			
			switch ( $_GET['action'] ) :
				
				case 'validate':
					
					//Comprobamos que no estuviera ya validado
					$check = $wpdb->get_results("SELECT * FROM ". WP_CALENDAR_TABLE_Aula . " WHERE event_id = '" . $event->event_id . "' AND event_valid = 'v' LIMIT 1");
					if(empty($check))
					{
						$last_update = date("Y-m-d");
						
						$wpdb->get_results("UPDATE " . WP_CALENDAR_TABLE_Aula . " SET event_valid = 'v',event_update='".$last_update."' WHERE event_id ='" . $get_event_by_md5ID[0]->event_id . "'");
						$message = __('El evento ha sido aprobado.', 'event-change-status');
					
						$notify_message1_solicitante = __('Su solicitud ha sido autorizada: ', 'event-change-status') ."\r\n\n";
						//Cuerpo del email
						$notify_message .= __('Email del solicitante: ', 'event-change-status') . $event->event_email . "\r\n";
						$notify_message .= __('Teléfono: ', 'event-change-status') . $event->event_phone . "\r\n";
						$notify_message .= __('Dni: ', 'event-change-status') . $event->event_phone . "\r\n";
						$notify_message .= __('Título del Evento: ', 'event-change-status') . $event->event_title . "\r\n";
						$notify_message .= __('Descripción: ', 'event-change-status') . $event->event_desc . "\r\n\r\n";
						$notify_message .= __('Aula: ', 'event-change-status') . $result_category[0]->category_name . "\r\n";
						$notify_message .= __('Centro/Departamento: ', 'event-change-status') . $event->event_department . "\r\n";
						$notify_message .= __('Sector: ', 'event-change-status') . $event->event_sector . "\r\n";
						$notify_message .= __('Material necesario: ', 'event-change-status');
						if($event->event_material != '')
						{
						list( $pc, $videoproyector, $megafonia, $otros ) = split( ';', $event->event_material );
						if($pc == 'PC')
							$notify_message .= __(' PC, ', 'event-change-status');
						if($videoproyector == 'Videoproyector')
							$notify_message .= __(' Videoproyector, ', 'event-change-status');
						if($megafonia == 'Megafonía')
							$notify_message .= __(' Megafonía, ', 'event-change-status');
						if($otros == 'Otros')
							$notify_message .= __(' Otros.', 'event-change-status');
						}	
						$notify_message .= "\r\n\r\n";
						$notify_message .= __('Observaciones: ', 'event-change-status') . $event->event_obs . "\r\n\r\n";
						$notify_message .= __('Fecha Inicio: ', 'event-change-status') . $event->event_begin . "\r\n";
						$notify_message .= __('Fecha Fin: ', 'event-change-status') . $event->event_end . "\r\n\r\n";
						$notify_message .= __('Hora Inicio: ', 'event-change-status') . $event->event_time_begin . "\r\n";
						$notify_message .= __('Hora Fin: ', 'event-change-status') . $event->event_time_end . "\r\n\r\n";
					
						//Cabecera del email
						$from_solicitante = "From: \"Facultad de Psicologia\" <no-reply@no-reply.org>";
						$reply_to_solicitante = "Reply-To: no-reply@no-reply.org";
						$message_headers_solicitante = "$from_solicitante\n"
						. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
						if ( isset($reply_to_solicitante) )
							$message_headers_solicitante .= $reply_to_solicitante . "\n";
						$message_solicitante = $notify_message1_solicitante.$notify_message;

						wp_mail($event->event_email, "Su solicitud ha sido autorizada", $message_solicitante, $message_headers_solicitante);
						wp_die(__($message, 'event-change-status'), '', 'response=200');
					}
					else
					{
						wp_die(__('El evento ya ha sido visitado. ;-)', 'event-change-status'), '', 'response=200');	
					}
					break;
				
				case 'unvalidate':
				
					//Comprobamos que no estuviera ya rechazado
					$check = $wpdb->get_results("SELECT * FROM ". WP_CALENDAR_TABLE_Aula . " WHERE event_id = '" . $event->event_id . "' AND event_valid = 'r' LIMIT 1");
					if(empty($check))
					{
						echo $message = __('Para rechazar el evento debe elegir el motivo entre uno de los siguientes: ', 'event-change-status')."\r\n\r\n";
						?>
					
						<form name="refuse_event" id="refuse_event" class="wrap" method="post" action="<?php $PHP_SELF ?>">
						<input type="hidden" name="action" value="refuse">
						<input type="hidden" name="event_id" value="<?php echo $event->event_id; ?>">
						<input type="hidden" name="event_email" value="<?php echo $event->event_email; ?>">
						<div id="linkadvanceddiv" class="postbox">
							<div style="float: left; width: 98%; clear: both;" class="inside">
                                <table cellpadding="5" cellspacing="5">
                                	 <br>
                                	 <tr>
										<input type="radio" name="motivo" value="Coincide con otra actividad autorizada con carácter previo" onchange="otroMotivo(this)">Coincide con otra actividad autorizada con carácter previo<br>
										<input type="radio" name="motivo" value="Coincide con otra actividad autorizada y prioritaria" onchange="otroMotivo(this)">Coincide con otra actividad autorizada y prioritaria<br>
										<input type="radio" name="motivo" value="Deberá aportar memoria de la actividad solicitada" onchange="otroMotivo(this)">Deberá aportar memoria de la actividad solicitada<br>
										<input type="radio" name="motivo" value="Otro motivo" onchange="otroMotivo(this)" onchange="otroMotivo(this)">Otro motivo<br>
										<td id="motivo_otro" class="motivo_otro_hidden"><textarea name="motivo_otro" class="input" rows="5" cols="35"><?php if ( !empty($desc) ) echo $desc; ?></textarea></td>
                               		 </tr>
                                </table>
                            </div>
                        </div>
                        <input type="submit" name="save" class="button bold" value="<?php _e("Enviar","calendarAula"); ?> &raquo;" />
						</form>
						<?php
					}
					else
					{
						wp_die(__('El evento ya ha sido visitado. ;-)', 'event-change-status'), '', 'response=200');
					}
					break;
				
			endswitch;

			//event_change_status__event_post( $get_event_by_md5ID, 1, true );
			
			//header('Content-Type: text/html; charset='.get_option('blog_charset').'');
	
		}

		die('');
	}
	event_change_status__process();

}

header('Content-Type: text/html; charset='.get_option('blog_charset').'');
die(__('The event has been yet visited, previosly?! ;-)', 'event-change-status'));
?>
