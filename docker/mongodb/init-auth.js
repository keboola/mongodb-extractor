use admin;
db.createUser({
    user: 'admin',
    pwd: 'admin',
    roles: [
        {
            role: "root",
            db: "admin"
        }
    ]
});

use test;
db.createUser({
    user: 'user',
    pwd: 'p#a!s@sw:o&r%^d',
    roles: [
        {
            role: "readWrite",
            db: "test"
        }
    ]
});
