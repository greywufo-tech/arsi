<?php
declare(strict_types=1);

/**
 * Pelanggaran aturan bisnis (stok habis, penawaran berakhir,
 * transisi status tidak valid, dan sejenisnya).
 *
 * Sengaja TIDAK bernama "DomainException" karena nama tersebut sudah dipakai
 * oleh kelas bawaan PHP (SPL).
 */
class BusinessRuleException extends RuntimeException
{
}