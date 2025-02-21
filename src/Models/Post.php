<?php

namespace Kasperworks\Models;

use Kasperworks\Model;
use Kasperworks\Attributes\PrimaryKey;
use Kasperworks\Attributes\Unique;
use Kasperworks\Attributes\Index;
use Kasperworks\Attributes\ForeignKey;
use Kasperworks\Attributes\Required;
use Kasperworks\Models\User;

class Post extends Model
{
    public static string $table = "post";

    #[PrimaryKey("id")]
    public $id;

    #[Unique("title")]
    public $title;

    public $content;

    #[Required]
    public User $user_id;
}
