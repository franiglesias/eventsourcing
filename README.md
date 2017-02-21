# eventsourcing
A simple Event Sourcing library

This is a work in progress.

[![Build Status](https://travis-ci.org/franiglesias/eventsourcing.svg?branch=master)](https://travis-ci.org/franiglesias/eventsourcing)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/cb895cff-62f6-473a-87ee-b1553f5f2d71/mini.png)](https://insight.sensiolabs.com/projects/cb895cff-62f6-473a-87ee-b1553f5f2d71)

Event Sourcing is a way to approach the management of application state. Instead of keeping the current state of the Domain Entities, we store the sequence of events or changes that led to that state.

This automatically provides us with a full change history of our system. As Fowler says, we can achieve the same with a clever log system, but Event Sourcing is based in the fact that the domain objects are completely managed through events, so we can:

- Rebuild the state of the system at any point rerunning the events.
- Query the system state at any point.
- Replay events to fix errors.

https://martinfowler.com/eaaDev/EventSourcing.html

One aspect in which Event Sourcing fails is finding things. For example, a simple find by criteria is very difficult to write. So, Event Sourcing is better used in conjunction with a CQRS pattern.

In CQRS pattern (Command Query Responsibility Segregation) Write and Read operations are completely separated concerns, managed by different agents, that communicates between them through events. This means that we have a write model and a read one, that can live and evolve separately.

This can be better understood with an example:

Imagine that our system sends a Command to create a blog post, with their correspondent title, body and author data. The Post is created and an Event is raised to announce exactly that a new Post was created (or even extra events, such as a Post Was Published event). The Event Store keeps such Event (Write Model) and the Event is passed to the System through a Message Bus. Several Listeners may then act in response to this event, creating several corresponding Read Models according to application needs. For example: build a full Post static view, or update a published Posts List table in a database, that can be used to perform queries against it.

One of  the great advantages of this approach is that you can create new views adding new Listeners or change the existing ones simply by rerunning the event story. The changes are not only reflected from the temporal moment that you apply them, instead of that, you can rebuild the entire system from the beginning.


## Installation

Require the dependency with composer

    composer require milhojas/eventsourcing

## Setup

You will need a `config/database.yml` or `config/config.yml` file with configuration to define connections with the database server. Structure is the same as that in a Symfony app:

    doctrine:
        dbal:
            default_connection: 'example'
            connections:
                test:
                    driver:  'pdo_mysql'
                    user:    'root'
                    password: 'root'
                    dbname: 'testmilhojas'
                    host: 'localhost'
                    charset: utf8mb4
                travis:
                    driver:  'pdo_mysql'
                    user:    'root'
                    dbname: 'testmilhojas'
                    host: 'localhost'
                    charset: utf8mb4


### Create the events table in the database

Run the following command

    bin/eventsourcing events:setup

You are ready to run.

## Key concepts

### Event

A event is a message that communicates something that happened in the past and can be interesting for any other part of the system. Any part of the system can raise an event, and any other part of the system can be listening to that event.

In the Event Sourcing paradigm, Domain Entities communicate their state changes raising events. In fact, they are built in such a way that they apply the Events to change state. We encapsulate that using "traditional" methods that expose the public interface of the Entity, to manage them through commands, for example.

Events carry every information needed about the changes. For example, an UserAddProductToBasket Event must carry information about user, product, basket and product quantity.

### Event Message

An Event Message is an envelope for Events. It carries the Event itself and metadata information for it, such as an id, entity information and version, and arbitrary metadata.

Internally, Event Message uses a Event Envelope object (deprecated) and a Entity object, that carries the minimal data needed to reconstitute the real entity (class name, id and version).

_Note: Entity could be renamed to EntityMetadata for clarity_

### Event Stream

A Event Stream is a collection of Event Messages that can build a concrete EventSourcedEntity.

### Event Sourced Entity

In our implementation an Event Sourced Entity is an Entity that works through Event Sourcing. EventSourcedEntity is an abstract base class that provides basic utilities to achieve that. That utilities include:

- A reconstitute constructor method that gets an EventStream and applies to the Entity to regenerate it.
- An EventStream and methods to store the Events that the Entity will raise.
- A method to communicate the events applied.
- Version tracking.

Event Sourced Entities are versioned, because every event implies a new version of the Entity. This should be controlled to ensure the proper version of the state when needed.

### Event Store

The Event Store, as its name implies, is the place where Events are stored. The Event Store itself is an abstraction. We provide both a Memory based Event Store and a Database based Event Store. Strictly speaking, the Event Store keeps Event Messages.

The Event Store can be seen as a flat table that stores the Event and meta data information to replay it when needed. We can use a unique Event Store for all of our entities, or not. Our implementation is basic, but it could be extended to support other storages. You can "store" entities storing the events that was applied to them, and you can "read" entities reading all the events stored that refer to them, until the version desired.

### Snapshots

Storing many events and having to retrieve all of them to reconstitute an Entity can be a huge and slow task in some systems. One solution to this problem is to create a snapshots system that can "fix" the state of an entity in some point of its history so we only need to retrieve a fraction o newer events to reconstitute it.

To date, we don't provide snapshots. The basic idea is to determine a point in the story of the object that we can use as starting point and get only newer events starting there. For example, imagine a blog system where we snapshot posts when they are flagged to publish because we don't expect to change them after that.

More documentation soon...
