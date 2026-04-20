DELIMITER $$
CREATE TRIGGER actualizar_stock_despues_de_rechazado
AFTER UPDATE ON pedidos
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE producto_id INT;
    DECLARE cantidad_pedida INT;
    DECLARE cur CURSOR FOR
        SELECT IDPRODUCTO, CANTIDAD
        FROM detalle_pedido
        WHERE IDPEDIDO = NEW.IDPEDIDO;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF NEW.ESTADO_PAGO = 'REJECTED' AND OLD.ESTADO_PAGO <> 'REJECTED' 
    AND NEW.NUMERO_PEDIDO LIKE 'PED-001%'
    THEN
        OPEN cur;

        read_loop: LOOP
            FETCH cur INTO producto_id, cantidad_pedida;
            IF done THEN
                LEAVE read_loop;
            END IF;

            UPDATE productos
            SET CANTIDAD_PRODUCTO = CANTIDAD_PRODUCTO + cantidad_pedida
            WHERE IDPRODUCTO = producto_id;
        END LOOP;

        CLOSE cur;
    END IF;
END$$
DELIMITER ;