-- we don't know how to generate schema main (class Schema) :(
create table profiles
(
    profile_id char(32) not null
        constraint profiles_pk
        primary key,
    profiles text not null
);

create table profiles_info
(
    id char(32) not null
        constraint profiles_info_pk
        primary key,
    url text,
    request_time timestamp not null,
    method varchar(20) not null,
    main_ct bigint,
    main_wt bigint,
    main_cpu bigint,
    main_mu bigint,
    main_pmu bigint,
    application varchar(150) default NULL,
    version varchar(150) default NULL,
    branch varchar(150) default NULL,
    controller varchar(150) default NULL,
    action varchar(150) default NULL,
    remote_addr var_char(30) default null,
    session_id varchar(150) default null
);

create unique index profiles_info_id_uindex
    on profiles_info (id);

create table profiles_meta
(
    profile_id char(32) not null
        constraint profiles_meta_pk
        primary key,
    meta text not null
);

create unique index profiles_meta_profile_id_uindex
    on profiles_meta (profile_id);

create table watched
(
    id integer not null
        constraint watched_pk
        primary key autoincrement,
    name text not null
);

create unique index watched_id_uindex
    on watched (id);

