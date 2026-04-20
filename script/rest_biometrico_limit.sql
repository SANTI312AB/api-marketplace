CREATE EVENT reset_biometrico_limite
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO BEGIN
    -- Resetear conteo biometrico usuario
    update usuarios
    SET   LIMITE_BIOMETRICO = 0
    WHERE IDESTADO= 16
    AND   FECHA_BIOMETRICO < NOW() - interval 24 hour;

END