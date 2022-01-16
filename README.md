Hello!

Here some comments about the task:

1. It is a bad practice to pass one URL in the another. I think at least it should be encoded somehow or pass separated arguments.
    11. It's not safe to send secure_key via GET request.
2. There is no 'service_type' in the order_tables. I assumed that it is a 'service_id'.
3. There is no description for the case when order_id isn't present in the 'order_charges' table. I suppose there are two options for this case:
    31. Don't process it further. (I did)
    32. Process it with zeros 'price_entity_id' and 'value'.
4. There is no description for the case when order_submit() throws an exception and no invoice_id returned. I've equated it to zero in this case.