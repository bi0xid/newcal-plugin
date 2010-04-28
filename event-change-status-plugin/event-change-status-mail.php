<?php

function event_change_status__event_post( $event_id, $event_valid) {
	global $wpdb, $event_change_status__url;
	
	
	if ( $destinationMail = get_option('change-event-status-mail') ) {
		// Usamos la opcion
		
	}else{
		// Usamos el administrador
		$destinationMail = get_option('admin_email');
		
	}
	
	if ( !empty($destinationMail) ) {
		
		if ( $event_id > 0 ) {
			
				$query_event = "SELECT * FROM " . wp_calendarAula . " WHERE event_id='" . mysql_escape_string($event_id) . "' LIMIT 1";
				$result = $wpdb->get_results($query_event);

				
	    		if ( empty($result) || empty($result[0]->event_id) )
	     		 {
                	?>
					<div class="topsegundo"><p><strong><?php _e('Error','event-change-status'); ?>:</strong> <?php _e('An event with the details you submitted could not be found in the database. This may indicate a problem with your database or the way in which it is configured.','event-change-status'); ?></p></div>
					<?php
	      		}
	    		

				$event = $result[0];
				$blogname = get_option('blogname');
				
				$query_category = "SELECT * FROM " . wp_calendar_categoriesAula . " WHERE category_id='" . $event->event_category . "' LIMIT 1";
				$result_category = $wpdb->get_results($query_category);
				
				if ( empty($result_category) || empty($result_category[0]->category_id) )
	     		 {
                	?>
					<div class="topsegundo"><p><strong><?php _e('Error','event-change-status'); ?>:</strong> <?php _e('A category with the details you submitted could not be found in the database. This may indicate a problem with your database or the way in which it is configured.','event-change-status'); ?></p></div>
					<?php
	      		}
				
				$notify_message = '';
				
				if ( $event_valid == 'n' ) {

					$notify_message1_admin = __('Evento NO APROBADO: ', 'event-change-status') . "\r\n" . __('Solicitud de Reserva de espacios de la Facultad de Psicología.', 'event-change-status')."\r\n\n";
					$notify_message1_solicitante = __('Evento NO APROBADO: ', 'event-change-status') . "\r\n" . __('Su solicitud ha sido recibida con éxito por el sistema. En breve, nos pondremos en contacto con Vd. con la autorización o, en su caso, denegación de su petición.', 'event-change-status')."\r\n\n";
					
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
					$notify_message2 = "\t".sprintf( __('Para APROBAR la reserva pinche aquí: %s', 'event-change-status'), $event_change_status__url . "/event-change-status-check.php?action=validate&becid=".$result[0]->event_id) . "\r\n";
					$notify_message2 .= "\t".sprintf( __('Para RECHAZAR la reserva pinche aquí: %s', 'event-change-status'), $event_change_status__url . "/event-change-status-check.php?action=unvalidate&becid=".$result[0]->event_id) . "\r\n";
						
					
					$subject = sprintf( __('[%1$s] Solicitud de reserva del aula: "%2$s"', 'event-change-status'), $blogname, $result_category[0]->category_name );
					
				}elseif ( $event_valid == 'y' ) {
					
					$notify_message1_admin = __('Evento APROBADO: ', 'event-change-status') . "\r\n" . __('Solicitud de anulación de reserva de espacios de la Facultad de Psicología.', 'event-change-status')."\r\n\n";
					$notify_message1_solicitante = __('Evento APROBADO: ', 'event-change-status') . "\r\n" . __('Su solicitud ha sido recibida con éxito por el sistema. En breve, nos pondremos en contacto con Vd. con la autorización o, en su caso, denegación de su petición.', 'event-change-status')."\r\n\n";
					
					
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
					$notify_message2 = "\t".sprintf( __('Para APROBAR la reserva pinche aquí: %s', 'event-change-status'), $event_change_status__url . "/event-change-status-check.php?action=validate&becid=".$result[0]->event_id) . "\r\n";
					$notify_message2 = "\t".sprintf( __('Para RECHAZAR la reserva pinche aquí: %s', 'event-change-status'), $event_change_status__url . "/event-change-status-check.php?action=unvalidate&becid=".$result[0]->event_id) . "\r\n";
					
					$subject = sprintf( __('[%1$s] Solicitud de anulación de reserva del aula: "%2$s"', 'event-change-status'), $blogname, $result_category[0]->category_name );
					
				}
				
				if ( $notify_message ) {

					$from = "From: \"Facultad de Psicologia\" <".$event->event_email.">";
					$reply_to = "Reply-To: no-reply@no-reply.org";
					$message_headers = "$from\n"
						. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
					if ( isset($reply_to) )
						$message_headers .= $reply_to . "\n";
					$message_admin = $notify_message1_admin.$notify_message.$notify_message2;
					
					wp_mail($destinationMail, $subject, $message_admin, $message_headers);
					/*
					$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
					if ( '' == $event->event_author ) {
						$from = "From: \"$blogname\" <$wp_email>";
						if ( '' != $event->event_author_email )
							$reply_to = "Reply-To: $event->event_author_email";
					} else {
						$from = "From: \"$event->event_author\" <$wp_email>";
						if ( '' != $event->event_author_email )
							$reply_to = "Reply-To: \"$event->event_author_email\" <$event->event_author_email>";
					}*/
					
					$from_solicitante = "From: \"Facultad de Psicologia\" <no-reply@no-reply.org>";
					$reply_to_solicitante = "Reply-To: no-reply@no-reply.org";
					$message_headers_solicitante = "$from_solicitante\n"
						. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
					if ( isset($reply_to_solicitante) )
						$message_headers_solicitante .= $reply_to_solicitante . "\n";
					$message_solicitante = $notify_message1_solicitante.$notify_message;

					wp_mail($event->event_email, "Solicitud recibida", $message_solicitante, $message_headers_solicitante);
				}
				
			
		}
		
	}
}


?>
