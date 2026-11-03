<?php

function statusBerat(int $beratKg): string
{
    return $beratKg >= 10 ? 'Besar' : 'Reguler';
}
