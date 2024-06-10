<script lang="ts">
  import { getUserOrders, deleteOrder, updateOrder } from '../../../api/orders';
  import type Orders from '../../../interfaces/orders';
  import { writable } from 'svelte/store';
  import { onMount } from 'svelte';

  // Create a writable store for orders
  const orders = writable<Orders[]>([]);

  // Store for keeping track of the currently edited order
  const editedOrder = writable<Orders | null>(null);

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

  async function handleDelete(orderId: number) {
    try {
      await deleteOrder(orderId);
      // Update the orders store by filtering out the deleted order
      orders.update(currentOrders => currentOrders.filter(order => order.id !== orderId));
    } catch (error) {
      console.error('Error deleting order:', error);
    }
  }

  function startEditing(order: Orders) {
    editedOrder.set(order);
  }

  function cancelEditing() {
    editedOrder.set(null);
  }

  async function handleUpdate() {
    const orderToUpdate = $editedOrder;
    if (!orderToUpdate) return;

    try {
      const result = await updateOrder(orderToUpdate.id, orderToUpdate);
      orders.update(currentOrders => {
        return currentOrders.map(order => order.id === result.id ? result : order);
      });
      editedOrder.set(null);
    } catch (error) {
      console.error('Error updating order:', error);
    }
  }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">My Orders</h1>

    {#if $orders.length > 0}
      {#each $orders as order (order.id)}
        <div class="bg-white rounded-lg overflow-hidden shadow-lg mb-6">
          {#if $editedOrder?.id === order.id}
            <!-- Edit Mode -->
            <div class="p-4">
              <h2 class="text-lg font-semibold mb-4">Edit Order ID: {order.id}</h2>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.name}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.phone}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.email}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="method">Method</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.method}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.address}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="payment_status">Payment Status</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.payment_status}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Status</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="text" bind:value={$editedOrder.status}>
              </div>
              <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="total_price">Total Price</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" type="number" bind:value={$editedOrder.total_price}>
              </div>

              <div class="flex justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" on:click={handleUpdate}>
                  Save
                </button>
                <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded" on:click={cancelEditing}>
                  Cancel
                </button>
              </div>
            </div>
          {:else}
            <!-- View Mode -->
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

              <!-- Edit Order Button -->
              <div class="mt-4 flex justify-between">
                <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" on:click={() => startEditing(order)}>
                  Edit Order
                </button>
                <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" on:click={() => handleDelete(order.id)}>
                  Delete Order
                </button>
              </div>
            </div>
          {/if}
        </div>
      {/each}
    {:else}
      <p class="text-gray-600 text-center">No orders found.</p>
    {/if}
  </div>
</div>
