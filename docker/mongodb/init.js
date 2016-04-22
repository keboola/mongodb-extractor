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
    pwd: 'user',
    roles: [
        {
            role: "readWrite",
            db: "test"
        }
    ]
});
