create table profiles_info
(
    id varchar(100) not null
        constraint profiles_info_pk
        primary key,
    url text,
    simple_url text,
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

create table profiles
(
	profile_id varchar(100) not null
		constraint profiles_pk
			primary key
		references profiles_info
			on update cascade on delete cascade,
	profiles text not null
);

create unique index profiles_info_id_uindex
    on profiles_info (id);

create table profiles_meta
(
    profile_id varchar(100) not null
        constraint profiles_meta_pk
			primary key
		references profiles_info
			on update cascade on delete cascade,
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

