<?php

namespace PdoWithNT;

use Exception;
use PDO;

/**
 * Support for nested transactions in PDO.
 *
 * @author Stas Lozitskiy
 */
class PdoWithNT extends PDO
{
    protected int $transLevel = 0;

    public function beginTransaction(): bool
    {
        if ($this->transLevel == 0) {
            $res = parent::beginTransaction();
        } else {
            $res = (bool)$this->exec("SAVEPOINT LEVEL$this->transLevel");
        }
        $this->transLevel++;

        return $res;
    }

    public function commit(): bool
    {
        try {
            $this->transLevel--;
            if ($this->transLevel == 0) {
                $res = parent::commit();
            } else {
                $res = (bool)$this->exec("RELEASE SAVEPOINT LEVEL$this->transLevel");
            }
        } catch (Exception $ex) {
            throw new Exception("Probably transaction already closed", 0, $ex);
        }

        return $res;
    }

    public function rollBack(Exception $exSrc = null): bool
    {
        try {
            $this->transLevel--;
            if ($this->transLevel == 0) {
                $res = parent::rollBack();
            } else {
                $res = (bool)$this->exec("ROLLBACK TO SAVEPOINT LEVEL$this->transLevel");
            }
        } catch (Exception) {
            /** Misuse of transactions example:
             *
             * try {
             *     $db->beginTransaction();
             *
             *     try {
             *         throw Exception(); // - wrong!
             *
             *         $db->beginTransaction();
             *
             *         // ...
             *     } catch (Exception $e) {
             *         $db->rollBack();
             *         throw $e;
             *     }
             * } catch (Exception $e) {
             *     $db->rollBack();
             * }
             */

            $msg = "Error rolling back transaction. Maybe there is some Exception thrown before opening transaction";

            throw new Exception($msg, 0, $exSrc);
        }

        return $res;
    }
}
