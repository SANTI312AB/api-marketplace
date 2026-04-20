CREATE EVENT actualizar_pedidos_retirados
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO BEGIN
    -- Actualizar pedidos 'A DOMICILIO'
    
	UPDATE pedidos 
    SET IDESTADO = 22
    WHERE ESTADO_PAGO = 'APPROVED'
      AND FECHA_PEDIDO < NOW() - INTERVAL 96 HOUR
      AND TIPO_ENVIO = 'A DOMICILIO'
      AND IDESTADO = 26
      AND (ID_METODOENVIO !=1 OR ID_METODOENVIO !=2 or ID_METODOENVIO !=3 )
             and (UBICACION_REFERENCIA_PEDIDO <> 'PRUEBA')
            and (PROVINCIA_CLIENTE <> '')
                and (REGION_CLIENTE <> '')
                    and (PEDIDO_SUBTOTAL <> '')
                        and (PEDIDO_IVA <> '')
                            and (PEDIDO_TOTAL <> '')
                                and ( PEDIDO_TOTAL_FINAL <> '');

    -- Actualizar pedidos 'RETIRO EN TIENDA FISICA'
    UPDATE pedidos
    SET IDESTADO_RETIRO = 22
    WHERE ESTADO_PAGO = 'APPROVED'
      AND FECHA_PEDIDO < NOW() - INTERVAL 96 HOUR
      AND TIPO_ENVIO = 'RETIRO EN TIENDA FISICA'
      AND IDESTADO_RETIRO = 26
      AND (ID_METODOENVIO !=1 OR ID_METODOENVIO !=2 or ID_METODOENVIO !=3 )
             and (UBICACION_REFERENCIA_PEDIDO <> 'PRUEBA')
            and (PROVINCIA_CLIENTE <> '')
                and (REGION_CLIENTE <> '')
                    and (PEDIDO_SUBTOTAL <> '')
                        and (PEDIDO_IVA <> '')
                            and (PEDIDO_TOTAL <> '')
                                and ( PEDIDO_TOTAL_FINAL <> '');

    -- Actualizar pedidos 'AMBOS' casos
    UPDATE pedidos 
    SET IDESTADO = 22,
        IDESTADO_RETIRO = 22
    WHERE ESTADO_PAGO = 'APPROVED'
      AND FECHA_PEDIDO < NOW() - INTERVAL 96 HOUR
      AND TIPO_ENVIO = 'AMBOS'
      AND IDESTADO = 26
      AND IDESTADO_RETIRO = 26
      AND (ID_METODOENVIO !=1 OR ID_METODOENVIO !=2 or ID_METODOENVIO !=3 )
              and (UBICACION_REFERENCIA_PEDIDO <> 'PRUEBA')
            and (PROVINCIA_CLIENTE <> '')
                and (REGION_CLIENTE <> '')
                    and (PEDIDO_SUBTOTAL <> '')
                        and (PEDIDO_IVA <> '')
                            and (PEDIDO_TOTAL <> '')
                                and ( PEDIDO_TOTAL_FINAL <> '');
END