<?php
function podeVerPerfil(string $visibilidade, int $visitante, int $titular, bool $bloqueado): bool
{
    return !$bloqueado && ($visitante === $titular || $visibilidade === 'publico');
}
