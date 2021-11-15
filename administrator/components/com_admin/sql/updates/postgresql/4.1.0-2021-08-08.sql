--
-- Table structure for table "#__scheduler_tasks"
--

CREATE TABLE IF NOT EXISTS "#__scheduler_tasks"
(
    "id" serial NOT NULL,
    "asset_id" bigint NOT NULL DEFAULT '0',
    "title" varchar(255) NOT NULL,
    "type" varchar(128) NOT NULL,
    "execution_rules" text,
    "cron_rules" text,
    "state" smallint NOT NULL DEFAULT '0',
    "last_exit_code" int NOT NULL DEFAULT '0',
    "last_execution" timestamp without time zone,
    "next_execution" timestamp without time zone,
    "times_executed" int NOT NULL DEFAULT '0',
    "times_failed" int DEFAULT '0',
    "locked" timestamp without time zone,
    "priority" smallint NOT NULL DEFAULT '0',
    "ordering" bigint DEFAULT 0 NOT NULL,
    "params" text NOT NULL,
    "note" text DEFAULT '',
    "created" timestamp without time zone NOT NULL,
    "created_by" bigint NOT NULL DEFAULT '0',
    "checked_out" integer,
    "checked_out_time" timestamp without time zone,
    PRIMARY KEY ("id")
);

CREATE INDEX "#__scheduler_tasks_idx_type" ON "#__scheduler_tasks" ("type");
CREATE INDEX "#__scheduler_tasks_idx_state" ON "#__scheduler_tasks" ("state");
CREATE INDEX "#__scheduler_tasks_idx_last_exit" ON "#__scheduler_tasks" ("last_exit_code");
CREATE INDEX "#__scheduler_tasks_idx_next_exec" ON "#__scheduler_tasks" ("next_execution");
CREATE INDEX "#__scheduler_tasks_idx_locked" ON "#__scheduler_tasks" ("locked");
CREATE INDEX "#__scheduler_tasks_idx_priority" ON "#__scheduler_tasks" ("priority");
CREATE INDEX "#__scheduler_tasks_idx_checked_out" ON "#__scheduler_tasks" ("checked_out");
