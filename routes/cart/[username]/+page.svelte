<script lang="ts">
  import { onMount } from 'svelte';
  import type CartItem from '../../../interfaces/carts';
  import { getUserCart } from '../../../api/carts';
  import { writable } from 'svelte/store';

  // Create a writable store for cart items
  const cartItems = writable<CartItem[]>([]);

  onMount(async () => {
    try {
      // Get the username from the URL path
      const username = window.location.pathname.split('/')[2];
      
      if (!username) {
        throw new Error('Username parameter is missing');
      }

      console.log("Username:", username);
      const fetchedCartItems = await getUserCart(username);
      // Update the cart items store with the fetched cart items
      cartItems.set(fetchedCartItems);
      console.log("cartItems: ", $cartItems);
    } catch (error) {
      console.error('Error fetching cart items:', error);
    }
  });
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100">
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6 text-center">Shopping Cart</h1>

    {#if $cartItems.length > 0}
      {#each $cartItems as item, index}
        <div class="bg-white rounded-lg overflow-hidden shadow-lg mb-6 border-2 border-black">
          <div class="bg-gray-100 px-4 py-2 border-b">
            <h2 class="text-lg font-semibold">Product ID: {item.productId}</h2>
            <p class="text-sm text-gray-600">Name: {item.name}</p>
          </div>
          <div class="p-4">
            <p class="mb-2"><span class="font-semibold">Quantity:</span> {item.quantity}</p>
            <p class="mb-2"><span class="font-semibold">Price:</span> ${item.pricePerItem}</p>
          </div>
        </div>
        {#if index !== $cartItems.length - 1}
          <div class="mb-6"></div> <!-- Add space between products -->
        {/if}
      {/each}
    {:else}
      <p class="text-gray-600 text-center">Your cart is empty.</p>
    {/if}
  </div>
</div>
