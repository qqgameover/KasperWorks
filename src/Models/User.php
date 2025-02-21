<?php namespace Kasperworks\Models;

use Kasperworks\Attributes\Index;
use Kasperworks\Attributes\PrimaryKey;
use Kasperworks\Attributes\ForeignKey;
use Kasperworks\Attributes\Unique;
use Kasperworks\Attributes\Required;
use Kasperworks\Model;

class User extends Model
{
    public static string $table = "users";

    #[PrimaryKey("id")]
    public int $id;

    #[Unique("email"), Required]
    public string $email;

    #[Required]
    public string $password;
}
