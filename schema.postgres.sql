create table profiles_info
(
    id varchar(100) not null
        constraint profiles_info_pkey
            primary key,
    url text default null,
    simple_url text default null,
    request_time timestamp default now(),
    method varchar(20) not null,
    main_ct bigint,
    main_wt bigint,
    main_cpu bigint,
    main_mu bigint,
    main_pmu bigint,
    application varchar(150),
    version varchar(150),
    branch varchar(150),
    controller varchar(150),
    action varchar(150),
    remote_addr varchar(30),
    session_id varchar(150)
);

alter table profiles_info owner to postgres;

create table profiles
(
    profile_id varchar(100) not null
        constraint profiles_pkey
            primary key
        constraint profiles_profiles_info_id_fk
            references profiles_info
            on update cascade on delete cascade,
    profiles jsonb not null
);

alter table profiles owner to postgres;

create table profiles_meta
(
    profile_id varchar(100) not null
        constraint profiles_meta_pkey
            primary key
        constraint profiles_meta_profiles_info_id_fk
            references profiles_info
            on update cascade on delete cascade,
    meta jsonb not null
);

alter table profiles_meta owner to postgres;

create table watched
(
    id bigserial not null
        constraint watched_pkey
            primary key,
    name text not null
);

alter table watched owner to postgres;

