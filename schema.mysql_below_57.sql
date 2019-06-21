create table profiles_info
(
    id varchar(100) not null
        primary key,
    url text null,
    request_time timestamp default current_timestamp() not null,
    method varchar(20) not null,
    main_ct bigint null,
    main_wt bigint null,
    main_cpu bigint null,
    main_mu bigint null,
    main_pmu bigint null,
    application varchar(150) null,
    version varchar(150) null,
    branch varchar(150) null,
    controller varchar(150) null,
    action varchar(150) null,
    remote_addr varchar(30) null,
    session_id varchar(150) null
);

create table profiles
(
    profile_id varchar(100) not null
        primary key,
    profiles longtext collate utf8mb4_bin not null,
    constraint profiles_profiles_info_id_fk
        foreign key (profile_id) references profiles_info (id)
            on update cascade on delete cascade
);

create table profiles_meta
(
    profile_id varchar(100) not null
        primary key,
    meta text not null,
    constraint profiles_meta_profiles_info_id_fk
        foreign key (profile_id) references profiles_info (id)
            on update cascade on delete cascade
);

create table watched
(
    id int auto_increment
        primary key,
    name text not null collate utf8mb4_bin not null
);


