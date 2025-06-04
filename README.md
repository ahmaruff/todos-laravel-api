# Todo List APIs Backend (Laravel)

A RESTful API backend built with **Laravel**, designed to manage todo items with support for:
- Creating todos
- Exporting filtered data to Excel
- Generating chart-ready summary data

## Project Goals

The primary objective of this project was to implement all features described in the original specification document, which can be found in the [`SPEC.md`](./SPEC.md) file.

In addition to fulfilling the functional requirements, this implementation aims to demonstrate:
- Adherence to modern development practices
- Understanding of API design standards
- Consideration of long-term maintainability and scalability

Some aspects of the implementation differ slightly from the specification. These variations are intentional and based on common industry patterns and practical trade-offs.

---

## Design Decisions and Implementation Notes

While the original specification included certain implementation suggestions — such as using database ENUM types and returning unstructured JSON responses — alternative approaches have been adopted in this project. The following sections outline these choices and the rationale behind them.

### 1. Avoiding ENUM Types in the Database

> **Original suggestion**: Use ENUM types for fields like `status` and `priority`  
> **Implementation choice**: Enforce allowed values at the application layer

#### Rationale:
Using ENUM types directly in the database schema can lead to complications when modifying or extending values in production environments. Instead, validation is performed at the Laravel request and model level using constants and form requests.

This approach offers:
- Greater flexibility when introducing new statuses or priorities
- Easier migration between versions without altering the schema
- A clearer separation between business logic and data storage concerns

It also aligns with how many large-scale applications handle enumerated values, especially when those values may evolve over time.

---

### 2. Consistent API Response Format (JSend Standard)

> **Original suggestion**: Return raw created object or error messages  
> **Implementation choice**: Responses follow the [JSend](https://github.com/omniti-labs/jsend)  specification

#### Rationale:
Standardizing response structures improves predictability for clients consuming the API. JSend provides a clear format for distinguishing between success and error states, and allows structured error details to be returned.

By adopting this standard, the API becomes more robust and easier to integrate with frontend applications or third-party services.

---

## Tech Stack

- **Framework**: Laravel 12.x
- **Database**: SQLite
- **API Response Format**: JSend standard

---

## Features Implemented

| Feature | Description |
|--------|-------------|
| Create Todo Item | POST `/api/todos` |
| Get Todos | GET `/api/todos` with filters |
| Get Todo Item | GET `/api/todos/{id}` |
| Export Todos to Excel | GET `/api/todos/export` with filters |
| Chart Data Endpoint | GET `/api/chart?type=[status\|priority\|assignee]` |
| Request Validation | Form requests and centralized error handling |
| Filter Support | Query parameters for filtering todos |
| Summary Row in Excel | Total todos and total time tracked |

---

## Copyright

© [Ahmad Ma'ruf](mailto:ahmadmaruf2701@gmail.com) - 2025
