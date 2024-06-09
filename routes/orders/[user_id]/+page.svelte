<script lang="ts">
  import { getUserOrders } from '../../../api/orders';
  import type Orders from '../../../interfaces/orders';
  import { writable } from 'svelte/store';
  import { onMount } from 'svelte';

  // Create a writable store for orders
  const orders = writable<Orders[]>([]);

  onMount(async () => {
    try {
      // Get the user ID from the URL path
      const userId = window.location.pathname.split('/')[2];
      
      if (!userId) {
        throw new Error('User ID parameter is missing');
      }

      console.log("User ID:", userId);

      // Fetch user's orders from the server
      const fetchedOrders = await getUserOrders(userId);

      // Update the orders store with the fetched orders
      orders.set(fetchedOrders);
      console.log("orders: ", $orders);

    } catch (error) {
      console.error('Error fetching orders:', error);
    }
  });
</script>
  
<div class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">My Orders</h1>

    {#if $orders.length > 0}
      {#each $orders as order (order.id)}
        <div class="bg-white rounded-lg overflow-hidden shadow-lg mb-6">
          <div class="bg-gray-100 px-4 py-2 border-b">
            <h2 class="text-lg font-semibold">Order ID: {order.id}</h2>
            <p class="text-sm text-gray-600">Placed on: {order.placed_on}</p>
          </div>
          <div class="p-4">
            <p><span class="font-semibold">Name:</span> {order.name}</p>
            <p><span class="font-semibold">Phone:</span> {order.phone}</p>
            <p><span class="font-semibold">Email:</span> {order.email}</p>
            <p><span class="font-semibold">Method:</span> {order.method}</p>
            <p><span class="font-semibold">Address:</span> {order.address}</p>
            <p><span class="font-semibold">Payment Status:</span> {order.payment_status}</p>
            <p><span class="font-semibold">Status:</span> {order.status}</p>
            <p><span class="font-semibold">Total Price:</span> ${order.total_price}</p>

            {#if order.items && order.items.length > 0}
              <div class="mt-4">
                <h3 class="text-lg font-semibold mb-2">Items</h3>
                <ul>
                  {#each order.items as item (item.product_id)}
                    <li class="mb-2">
                      Product ID: {item.product_id}, Quantity: {item.quantity}, Price Per Item: ${item.price_per_item}
                    </li>
                  {/each}
                </ul>
              </div>
            {:else}
              <p class="text-gray-600">No items in this order.</p>
            {/if}
          </div>
        </div>
      {/each}
    {:else}
      <p class="text-gray-600 text-center">No orders found.</p>
    {/if}
  </div>
</div>
