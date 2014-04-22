drop view _view_reporte_calltype;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `_view_reporte_calltype` AS select `e`.`id` AS `id`,`c`.`fecha` AS `fecha`,concat(`a`.`nombre`,_latin1' ',`a`.`apellido`) AS `cliente`,`a`.`ci` AS `ci`,`e`.`nombre` AS `campania`,`c`.`agente` AS `agente`,`c`.`telefono` AS `telefono`,`d`.`clase` AS `contactabilidad`,`d`.`descripcion` AS `mejor_calltype`,`b`.`id_gestion_mejor_calltype` AS `id_gestion_mejor_calltype`,`c`.`observacion` AS `observacion`,`b`.`fecha_agendamiento` AS `fecha_agendamiento`,`b`.`agente_agendado` AS `agente_agendado`,`a`.`origen` AS `origen` from ((((`cliente` `a` join `campania_cliente` `b`) join `gestion_campania` `c`) join `campania_calltype` `d`) join `campania` `e`) where ((`a`.`ci` = `b`.`ci`) and (`b`.`id_gestion_mejor_calltype` = `c`.`id`) and (`c`.`calltype` = `d`.`id`) and (`b`.`id_campania` = `e`.`id`)) order by `c`.`fecha` desc;