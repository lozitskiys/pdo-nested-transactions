# Info

Example of nested transactions use:
```php
try {
    $db->beginTransaction();

    // some logic here

    try {
        $db->beginTransaction();

        // some logic here

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```