# Kasperworks ORM Documentation

## 📌 Overview
Kasperworks ORM provides an elegant way to interact with your database using PHP classes instead of raw SQL queries.
It was also designed to be braindead simple to use, with a focus on readability and ease of use.
While it might lack some features, it's still a work in progress that I hope to improve over time.

---

## 🚀 Getting Started
Each table in your database should have a corresponding **Model** that extends `Model.php`.

### **1️⃣ Creating a Model**
Define a model that extends `Model` and specifies the table name, as well as the fields with their respective attributes:

```php
<?php namespace Kasperworks\Models;

use Kasperworks\Attributes\Index;
use Kasperworks\Attributes\PrimaryKey;
use Kasperworks\Attributes\ForeignKey;
use Kasperworks\Attributes\Unique;
use Kasperworks\Attributes\Required;
use Kasperworks\Traits\SoftDeletes;
use Kasperworks\Model;

class User extends Model
{

    //use SoftDeletes; Also supports softdeletes.
    public static string $table = "users";

    #[PrimaryKey("id")]
    public int $id;

    #[Unique("email"), Required]
    public string $email;

    #[Required]
    public string $password_hash;
}
```

---

## 🔍 Querying Data
### **2️⃣ Find a Record by ID**
```php
$user = User::find(1);
var_dump($user->toArray());
```

### **3️⃣ Fetch All Users**
```php
$users = User::query()->get();
print_r($users);
```

### **4️⃣ Filtering with `where`**
```php
$users = User::query()->where("email", "LIKE", "%@gmail.com%")->get();
print_r($users);
```

---

## ✨ Insert & Update Data

### **5️⃣ Insert a New Record**
```php
User::create([
    "email" => "test@example.com",
    "password_hash" => password_hash("secret", PASSWORD_BCRYPT),
    "name" => "John Doe",
]);
```

### **6️⃣ Update a Record**
```php

$user = User::find(13); //Example primary key value, in this case 13

$user = $user->update(['email' => 'testeded@gmail.com']); //Returns a new instance, instead of updating the Model object in place
var_dump($user->toArray());

```

### **Delete a record**
```php
User::find(13)->delete(); //Delete the corrosponding object in the db.
```

---

## 🔄 Creating Migrations
To create a new migration, execute the migration script in your terminal:
```sh
php ./CLI/generate_migration.php <Classname>
```


## 🔄 Running Migrations
To run migrations, execute the migration script in your terminal:
```sh
php ./CLI/run_migrations.php
```


---

### Connection to the DB
To connect to the database, create a .env file at the root of your project containg the following information:
```env
DB_HOST=
DB_NAME=
DB_USER=
DB_PASSWORD=
DB_CHARSET=
```
For now, only MYSQL is supported, but the plan is to add support for other databases soon.

---


## 🎯 Summary
✅ **Active Record-like querying**
✅ **Chained query building**
✅ **Auto-handled foreign keys & relationships**
✅ **Simple migrations**

Enjoy using Kasperworks ORM! 🚀
